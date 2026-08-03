<?php

namespace App\Services\Collector;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use RuntimeException;

/**
 * Parser chuyên dụng cho Siemens S7-1200 Web Server:
 *
 *   table#watch_table
 *      tr.var
 *          td[1] Name
 *          td[2] Address
 *          td[3] Display Format
 *          td[4] Monitor Value
 *          td[5] Modify Value
 *          td[6] Force
 *          td[7] Comment
 */
class PlcPortalTagParser
{
    private const FORMAT_TO_DATA_TYPE = [
        'BOOL' => 'BOOL',
        'FLOATING_POINT' => 'REAL',
        'DEC+/-' => 'DINT',
        'DEC' => 'UDINT',
        'HEX' => 'DWORD',
        'BIN' => 'DWORD',
        'CHARACTER' => 'CHAR',
        'UNICODE_CHARACTER' => 'WCHAR',
        'TIME_OF_DAY' => 'TIME_OF_DAY',
        'TIME' => 'TIME',
    ];

    public function parse(string $html): array
    {
        $html = trim($html);

        if ($html === '') {
            throw new RuntimeException('HTML PLC Portal đang rỗng.');
        }

        $document = new DOMDocument();

        libxml_use_internal_errors(true);

        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_COMPACT
        );

        libxml_clear_errors();

        if (!$loaded) {
            throw new RuntimeException('Không thể phân tích HTML PLC Portal.');
        }

        $xpath = new DOMXPath($document);

        $watchTable = $xpath->query('//table[@id="watch_table"]')->item(0);

        if (!$watchTable instanceof DOMElement) {
            throw new RuntimeException(
                'Không tìm thấy bảng Siemens Watch Table có id="watch_table".'
            );
        }

        $rows = $xpath->query(
            './/tr[contains(concat(" ", normalize-space(@class), " "), " var ")]',
            $watchTable
        );

        if (!$rows instanceof DOMNodeList || $rows->length === 0) {
            throw new RuntimeException(
                'Không tìm thấy dòng tag tr.var trong Siemens Watch Table.'
            );
        }

        $tags = [];

        foreach ($rows as $rowIndex => $row) {
            if (!$row instanceof DOMElement) {
                continue;
            }

            $tag = $this->parseRow($xpath, $row, $rowIndex);

            if ($tag === null) {
                continue;
            }

            $tags[$this->deduplicationKey($tag)] = $tag;
        }

        if ($tags === []) {
            throw new RuntimeException(
                'Đã tìm thấy Watch Table nhưng không đọc được tag hợp lệ.'
            );
        }

        return array_values($tags);
    }

    private function parseRow(
        DOMXPath $xpath,
        DOMElement $row,
        int $rowIndex
    ): ?array {
        $cells = $xpath->query('./td', $row);

        if (!$cells instanceof DOMNodeList || $cells->length < 4) {
            return null;
        }

        $sourceName = $this->cleanName(
            $this->nodeText($cells->item(0))
        );

        $address = $this->normalizeAddress(
            $this->nodeText($cells->item(1))
        );

        $displayFormat = $this->readSelectedDisplayFormat(
            $xpath,
            $cells->item(2)
        );

        $monitorValueText = $this->readMonitorValue(
            $xpath,
            $cells->item(3)
        );

        $comment = $cells->length >= 7
            ? $this->nullIfEmpty($this->nodeText($cells->item(6)))
            : null;

        if ($sourceName === null && $address === null) {
            return null;
        }

        if ($address !== null && !$this->isValidAddress($address)) {
            return null;
        }

        $sourceName ??= $address;

        if ($sourceName === null) {
            return null;
        }

        $dataType = $this->mapDataType(
            displayFormat: $displayFormat,
            address: $address,
            monitorValue: $monitorValueText
        );

        return [
            'address' => $address,
            'source_name' => $sourceName,
            'portal_name' => $sourceName,
            'data_type' => $dataType,
            'display_format' => $displayFormat,
            'value' => $this->normalizeValue(
                $monitorValueText,
                $dataType
            ),
            'raw_value' => $monitorValueText,
            'unit' => null,
            'comment' => $comment,
            'portal_row_index' => $rowIndex,
        ];
    }

    private function readSelectedDisplayFormat(
        DOMXPath $xpath,
        ?DOMNode $cell
    ): ?string {
        if (!$cell instanceof DOMElement) {
            return null;
        }

        $selected = $xpath->query(
            './/select[contains(concat(" ", normalize-space(@class), " "), " type-selection ")]/option[@selected]',
            $cell
        )->item(0);

        if (!$selected instanceof DOMNode) {
            $selected = $xpath->query('.//select/option[@selected]', $cell)
                ->item(0);
        }

        if (!$selected instanceof DOMNode) {
            $selected = $xpath->query('.//select/option[1]', $cell)
                ->item(0);
        }

        return $this->nullIfEmpty($this->nodeText($selected));
    }

    private function readMonitorValue(
        DOMXPath $xpath,
        ?DOMNode $cell
    ): ?string {
        if (!$cell instanceof DOMElement) {
            return null;
        }

        $clone = $cell->cloneNode(true);

        if (!$clone instanceof DOMElement) {
            return $this->nullIfEmpty($this->nodeText($cell));
        }

        $cloneXPath = new DOMXPath($clone->ownerDocument);

        $images = $cloneXPath->query('.//img', $clone);

        if ($images instanceof DOMNodeList) {
            for ($i = $images->length - 1; $i >= 0; $i--) {
                $image = $images->item($i);

                if ($image?->parentNode) {
                    $image->parentNode->removeChild($image);
                }
            }
        }

        $value = $this->nullIfEmpty($clone->textContent);

        if ($value !== null) {
            return $value;
        }

        $image = $xpath->query('.//img', $cell)->item(0);

        if ($image instanceof DOMElement) {
            $source = mb_strtolower($image->getAttribute('src'));

            if (str_contains($source, 'icontrue')) {
                return 'true';
            }

            if (str_contains($source, 'iconfalse')) {
                return 'false';
            }
        }

        return null;
    }

    private function mapDataType(
        ?string $displayFormat,
        ?string $address,
        ?string $monitorValue
    ): ?string {
        if ($displayFormat !== null) {
            $key = mb_strtoupper(
                str_replace(' ', '_', trim($displayFormat))
            );

            if (array_key_exists($key, self::FORMAT_TO_DATA_TYPE)) {
                return self::FORMAT_TO_DATA_TYPE[$key];
            }
        }

        if ($address !== null) {
            $upper = mb_strtoupper($address);

            if (preg_match('/^%(?:I|Q|M)\d+\.\d+$/', $upper)) {
                return 'BOOL';
            }

            if (preg_match('/^%(?:ID|QD|MD)\d+$/', $upper)) {
                return $this->looksFloatingPoint($monitorValue)
                    ? 'REAL'
                    : 'DWORD';
            }

            if (preg_match('/^%(?:IW|QW|MW)\d+$/', $upper)) {
                return 'WORD';
            }

            if (preg_match('/^%(?:IB|QB|MB)\d+$/', $upper)) {
                return 'BYTE';
            }
        }

        return null;
    }

    private function normalizeValue(
        ?string $value,
        ?string $dataType
    ): mixed {
        $value = $this->nullIfEmpty($value);

        if ($value === null) {
            return null;
        }

        if ($dataType === 'BOOL') {
            $normalized = mb_strtolower($value);

            if (in_array($normalized, ['true', '1', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['false', '0', 'off'], true)) {
                return false;
            }

            return null;
        }

        if (in_array($dataType, ['REAL', 'LREAL'], true)) {
            $normalized = str_replace(',', '.', $value);

            return is_numeric($normalized)
                ? (float) $normalized
                : null;
        }

        if (in_array(
            $dataType,
            ['BYTE', 'WORD', 'DWORD', 'INT', 'DINT', 'UDINT'],
            true
        )) {
            $normalized = trim($value);

            return preg_match('/^[+-]?\d+$/', $normalized)
                ? (int) $normalized
                : $normalized;
        }

        return $value;
    }

    private function cleanName(?string $value): ?string
    {
        $value = $this->nullIfEmpty($value);

        if ($value === null) {
            return null;
        }

        $value = preg_replace('/^["“”]+|["“”]+$/u', '', $value) ?? $value;
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeAddress(?string $value): ?string
    {
        $value = $this->nullIfEmpty($value);

        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+/u', '', $value) ?? $value;

        return mb_strtoupper($value);
    }

    private function isValidAddress(string $address): bool
    {
        return preg_match(
            '/^%(?:I|Q|M|IB|QB|MB|IW|QW|MW|ID|QD|MD|DB|T|C|PI|PQ)[A-Z0-9_.:\[\]-]*$/i',
            $address
        ) === 1;
    }

    private function looksFloatingPoint(?string $value): bool
    {
        $value = $this->nullIfEmpty($value);

        if ($value === null) {
            return false;
        }

        return preg_match(
            '/^[+-]?\d+[.,]\d+(?:[eE][+-]?\d+)?$/',
            trim($value)
        ) === 1;
    }

    private function deduplicationKey(array $tag): string
    {
        if (!empty($tag['address'])) {
            return 'address:' . mb_strtolower($tag['address']);
        }

        return 'name:' . hash(
            'sha256',
            mb_strtolower(trim($tag['source_name']))
        );
    }

    private function nodeText(?DOMNode $node): ?string
    {
        if (!$node instanceof DOMNode) {
            return null;
        }

        return $this->nullIfEmpty($node->textContent);
    }

    private function nullIfEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = html_entity_decode(
            $value,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
