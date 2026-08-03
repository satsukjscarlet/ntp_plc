<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\Product;
use App\Models\ProductionShift;
use App\Models\ShiftProductionDeclaration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class DailyProductionReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $report = $this->buildReport($filters);

        return view('production-reports.daily-detail', [
            ...$report,
            'filters' => $filters,
            'machines' => Machine::query()->orderBy('code')->get(),
            'products' => Product::query()->orderBy('material_code')->get(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $filters = $this->filters($request);
        $report = $this->buildReport($filters);
        $filename = 'bao-cao-tinh-hinh-san-xuat-ngay-' . now()->format('Ymd-His') . '.xlsx';

        $temporaryPath = tempnam(storage_path('app'), 'daily-report-');

        if ($temporaryPath === false) {
            abort(500, 'Không tạo được file tạm để xuất Excel.');
        }

        $xlsxPath = $temporaryPath . '.xlsx';
        @unlink($temporaryPath);

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Tình hình sản xuất');

            $shiftCount = max(1, $report['shifts']->count());
            $lastColumnIndex = 20 + ($shiftCount * 2);
            $lastColumn = Coordinate::stringFromColumnIndex($lastColumnIndex);

            $sheet->mergeCells("A1:{$lastColumn}1");
            $sheet->setCellValue('A1', 'BÁO CÁO TÌNH HÌNH SẢN XUẤT NGÀY CHI TIẾT');
            $sheet->mergeCells("A2:{$lastColumn}2");
            $sheet->setCellValue(
                'A2',
                'Từ ' . Carbon::parse($filters['date_from'])->format('d/m/Y')
                . ' đến ' . Carbon::parse($filters['date_to'])->format('d/m/Y')
            );
            $sheet->getStyle("A1:{$lastColumn}2")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

            $headers1 = [
                'Ngày',
                'Mã máy',
                'Tên máy',
                'Mã sản phẩm',
                'Tên sản phẩm',
                'ĐVT',
            ];

            $column = 1;
            foreach ($headers1 as $header) {
                $coordinate = Coordinate::stringFromColumnIndex($column++) . '4';
                $sheet->setCellValue($coordinate, $header);
            }

            $quantityStart = $column;
            foreach ($report['shifts'] as $shift) {
                $coordinate = Coordinate::stringFromColumnIndex($column++) . '5';
                $sheet->setCellValue($coordinate, $shift->name ?: $shift->code);
            }
            if ($report['shifts']->isEmpty()) {
                $coordinate = Coordinate::stringFromColumnIndex($column++) . '5';
                $sheet->setCellValue($coordinate, 'Ca');
            }

            $sheet->mergeCells(
                Coordinate::stringFromColumnIndex($quantityStart) . '4:'
                . Coordinate::stringFromColumnIndex($column - 1) . '4'
            );
            $sheet->setCellValue(
                Coordinate::stringFromColumnIndex($quantityStart) . '4',
                'Số lượng'
            );

            foreach ([
                'Tổng SL',
                'Chính phẩm',
                'Phế phẩm',
                'Phế liệu kg',
                'Số m/ĐVT',
                'KL chuẩn g/ĐVT',
            ] as $header) {
                $coordinate = Coordinate::stringFromColumnIndex($column++) . '4';
                $sheet->setCellValue($coordinate, $header);
            }

            $weightStart = $column;
            foreach ($report['shifts'] as $shift) {
                $coordinate = Coordinate::stringFromColumnIndex($column++) . '5';
                $sheet->setCellValue($coordinate, $shift->name ?: $shift->code);
            }
            if ($report['shifts']->isEmpty()) {
                $coordinate = Coordinate::stringFromColumnIndex($column++) . '5';
                $sheet->setCellValue($coordinate, 'Ca');
            }

            $sheet->mergeCells(
                Coordinate::stringFromColumnIndex($weightStart) . '4:'
                . Coordinate::stringFromColumnIndex($column - 1) . '4'
            );
            $sheet->setCellValue(
                Coordinate::stringFromColumnIndex($weightStart) . '4',
                'Trọng lượng kg'
            );

            foreach ([
                'Tổng KL kg',
                'Giờ dừng',
                'Mã nguyên nhân',
                'Nguyên nhân dừng',
                'Ghi chú',
                'Kiểm tra dữ liệu',
                'Phiếu nguồn',
            ] as $header) {
                $coordinate = Coordinate::stringFromColumnIndex($column++) . '4';
                $sheet->setCellValue($coordinate, $header);
            }

            for ($columnIndex = 1; $columnIndex <= $lastColumnIndex; $columnIndex++) {
                $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);
                $value = $sheet->getCell($columnLetter . '4')->getValue();

                $isQuantityChild = in_array(
                    $columnIndex,
                    range($quantityStart, $quantityStart + $shiftCount - 1),
                    true
                );
                $isWeightChild = in_array(
                    $columnIndex,
                    range($weightStart, $weightStart + $shiftCount - 1),
                    true
                );

                if ($value && !$isQuantityChild && !$isWeightChild) {
                    $sheet->mergeCells($columnLetter . '4:' . $columnLetter . '5');
                }
            }

            $rowNumber = 6;
            foreach ($report['rows'] as $row) {
                $values = [
                    $row['date']->format('d/m/Y'),
                    $row['machine_code'],
                    $row['machine_name'],
                    $row['product_code'],
                    $row['product_name'],
                    $row['unit'],
                ];

                foreach ($report['shifts'] as $shift) {
                    $values[] = $row['quantities'][$shift->id] ?? 0;
                }
                if ($report['shifts']->isEmpty()) {
                    $values[] = 0;
                }

                array_push(
                    $values,
                    $row['total_quantity'],
                    $row['good_quantity'],
                    $row['reject_quantity'],
                    $row['scrap_kg'],
                    $row['actual_length_m'],
                    $row['standard_weight_g']
                );

                foreach ($report['shifts'] as $shift) {
                    $values[] = $row['weights'][$shift->id] ?? 0;
                }
                if ($report['shifts']->isEmpty()) {
                    $values[] = 0;
                }

                array_push(
                    $values,
                    $row['total_weight_kg'],
                    $row['downtime_minutes'] / 60,
                    implode(', ', $row['downtime_codes']),
                    implode('; ', $row['downtime_reasons']),
                    implode('; ', $row['notes']),
                    implode('; ', $row['issues']),
                    implode(', ', $row['declaration_ids'])
                );

                foreach ($values as $index => $value) {
                    $coordinate = Coordinate::stringFromColumnIndex($index + 1)
                        . $rowNumber;
                    $sheet->setCellValue($coordinate, $value);
                }

                $rowNumber++;
            }

            $sheet->freezePane('G6');
            $sheet->setAutoFilter("A5:{$lastColumn}5");
            $sheet->getPageSetup()
                ->setOrientation('landscape')
                ->setFitToWidth(1)
                ->setFitToHeight(0);
            $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(4, 5);

            $sheet->getStyle("A4:{$lastColumn}5")
                ->getFont()
                ->setBold(true)
                ->getColor()
                ->setARGB('FFFFFFFF');
            $sheet->getStyle("A4:{$lastColumn}5")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FF0F766E');

            $lastDataRow = max(5, $rowNumber - 1);
            $sheet->getStyle("A4:{$lastColumn}{$lastDataRow}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setARGB('FFD0D5DD');
            $sheet->getStyle("A4:{$lastColumn}{$lastDataRow}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);

            foreach (range(1, $lastColumnIndex) as $columnIndex) {
                $width = in_array($columnIndex, [3, 5, 18, 19, 20], true)
                    ? 24
                    : 14;
                $sheet->getColumnDimension(
                    Coordinate::stringFromColumnIndex($columnIndex)
                )->setWidth($width);
            }

            (new Xlsx($spreadsheet))->save($xlsxPath);
            $spreadsheet->disconnectWorksheets();
        } catch (Throwable $exception) {
            @unlink($xlsxPath);
            report($exception);

            return back()->with(
                'error',
                'Không thể xuất Excel: ' . $exception->getMessage()
            );
        }

        return response()
            ->download(
                $xlsxPath,
                $filename,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
                ]
            )
            ->deleteFileAfterSend(true);
    }

    private function filters(Request $request): array
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->string('date_from'))->toDateString()
            : now()->startOfMonth()->toDateString();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->string('date_to'))->toDateString()
            : now()->toDateString();

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'machine_id' => $request->integer('machine_id') ?: null,
            'product_id' => $request->integer('product_id') ?: null,
            'status' => $request->string('status')->toString() ?: 'all_except_draft',
            'issues_only' => $request->boolean('issues_only'),
        ];
    }

    private function buildReport(array $filters): array
    {
        $shifts = ProductionShift::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->limit(3)
            ->get();

        $query = ShiftProductionDeclaration::query()
            ->with([
                'machine:id,code,name',
                'shift:id,code,name,display_order',
                'items.product:id,material_code,material_name,unit',
                'downtimeEntries.reason:id,code,name',
            ])
            ->whereBetween('production_date', [$filters['date_from'], $filters['date_to']])
            ->orderBy('production_date')
            ->orderBy('machine_id')
            ->orderBy('production_shift_id');

        if ($filters['machine_id']) {
            $query->where('machine_id', $filters['machine_id']);
        }

        if ($filters['product_id']) {
            $query->whereHas('items', fn ($itemQuery) => $itemQuery->where('product_id', $filters['product_id']));
        }

        if ($filters['status'] === 'all_except_draft') {
            $query->whereIn('status', ['submitted', 'approved']);
        } elseif ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        $declarations = $query->get();
        $rows = collect();

        foreach ($declarations as $declaration) {
            foreach ($declaration->items as $item) {
                if ($filters['product_id'] && (int) $item->product_id !== (int) $filters['product_id']) {
                    continue;
                }

                $key = implode('|', [
                    $declaration->production_date->format('Y-m-d'),
                    $declaration->machine_id,
                    $item->product_id ?: $item->product_code_snapshot,
                ]);

                if (!$rows->has($key)) {
                    $rows->put($key, [
                        'date' => $declaration->production_date,
                        'machine_id' => $declaration->machine_id,
                        'machine_code' => $declaration->machine?->code ?? '',
                        'machine_name' => $declaration->machine?->name ?? '',
                        'product_id' => $item->product_id,
                        'product_code' => $item->product_code_snapshot,
                        'product_name' => $item->product_name_snapshot,
                        'unit' => $item->unit_snapshot ?: 'ĐVT',
                        'actual_length_m' => (float) ($item->actual_length_m ?? $item->default_length_m_snapshot ?? 0),
                        'standard_weight_g' => (float) ($item->standard_weight_g_snapshot ?? 0),
                        'quantities' => [],
                        'weights' => [],
                        'total_quantity' => 0.0,
                        'total_weight_kg' => 0.0,
                        'good_quantity' => 0.0,
                        'reject_quantity' => 0.0,
                        'scrap_kg' => 0.0,
                        'notes' => [],
                        'declaration_ids' => [],
                        'downtime_minutes' => 0.0,
                        'downtime_codes' => [],
                        'downtime_reasons' => [],
                        'issues' => [],
                    ]);
                }

                $row = $rows->get($key);
                $shiftId = (int) $declaration->production_shift_id;
                $row['quantities'][$shiftId] = ($row['quantities'][$shiftId] ?? 0) + (float) $item->total_quantity;
                $row['weights'][$shiftId] = ($row['weights'][$shiftId] ?? 0) + (float) $item->converted_total_kg;
                $row['total_quantity'] += (float) $item->total_quantity;
                $row['total_weight_kg'] += (float) $item->converted_total_kg;
                $row['good_quantity'] += (float) $item->good_quantity;
                $row['reject_quantity'] += (float) $item->reject_quantity;
                $row['scrap_kg'] += (float) $item->scrap_quantity;
                $row['declaration_ids'][$declaration->id] = $declaration->id;

                foreach ([$item->note, $declaration->note] as $note) {
                    if ($note && !in_array($note, $row['notes'], true)) {
                        $row['notes'][] = $note;
                    }
                }

                $difference = abs((float) $row['total_quantity'] - ((float) $row['good_quantity'] + (float) $row['reject_quantity']));
                if ($row['total_quantity'] <= 0) $row['issues']['ZERO_TOTAL'] = 'Tổng sản lượng bằng 0';
                if ($difference > 0.001) $row['issues']['QTY_MISMATCH'] = 'Tổng sản lượng không bằng chính phẩm + phế phẩm';
                if ($row['standard_weight_g'] <= 0) $row['issues']['NO_WEIGHT'] = 'Thiếu khối lượng chuẩn';
                if (in_array(mb_strtolower(trim((string)$row['unit'])), ['m','mét','met'], true) && $row['actual_length_m'] <= 0) $row['issues']['NO_LENGTH'] = 'Thiếu chiều dài thực tế';

                $rows->put($key, $row);
            }
        }

        // Downtime là dữ liệu của phiếu/ca, chỉ gắn vào dòng sản phẩm đầu tiên của máy trong ngày để tránh cộng trùng.
        $groupedByDateMachine = $rows->groupBy(
            fn (array $row) => $row['date']->format('Y-m-d') . '|' . $row['machine_id'],
            preserveKeys: true
        );
        foreach ($groupedByDateMachine as $group) {
            $firstKey = $group->keys()->first();
            if ($firstKey === null) {
                continue;
            }

            $row = $rows->get($firstKey);
            $declarationIds = $group->flatMap(fn (array $item) => array_values($item['declaration_ids']))->unique();
            $machineDeclarations = $declarations->whereIn('id', $declarationIds);

            foreach ($machineDeclarations as $declaration) {
                foreach ($declaration->downtimeEntries as $entry) {
                    $row['downtime_minutes'] += (float) $entry->minutes;
                    if ($entry->reason?->code) {
                        $row['downtime_codes'][$entry->reason->code] = $entry->reason->code;
                    }
                    if ($entry->reason?->name) {
                        $label = $entry->reason->name . ' (' . number_format((float) $entry->minutes, 0, ',', '.') . "')";
                        $row['downtime_reasons'][] = $label;
                    }
                }
            }

            $rows->put($firstKey, $row);
        }

        $rows = $rows->sort(function (array $a, array $b): int {
            $dateCompare = $a['date']->format('Y-m-d') <=> $b['date']->format('Y-m-d');
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            $machineCompare = strnatcasecmp((string) $a['machine_code'], (string) $b['machine_code']);
            if ($machineCompare !== 0) {
                return $machineCompare;
            }

            return strnatcasecmp((string) $a['product_code'], (string) $b['product_code']);
        })->values();

        if ($filters['issues_only']) {
            $rows = $rows->filter(fn (array $row) => !empty($row['issues']))->values();
        }

        $dailyTotals = $rows->groupBy(fn (array $row) => $row['date']->format('Y-m-d'))
            ->map(function (Collection $group) use ($shifts): array {
                $quantities = [];
                $weights = [];
                foreach ($shifts as $shift) {
                    $quantities[$shift->id] = $group->sum(fn (array $row) => $row['quantities'][$shift->id] ?? 0);
                    $weights[$shift->id] = $group->sum(fn (array $row) => $row['weights'][$shift->id] ?? 0);
                }

                return [
                    'date' => $group->first()['date'],
                    'quantities' => $quantities,
                    'weights' => $weights,
                    'total_quantity' => $group->sum('total_quantity'),
                    'total_weight_kg' => $group->sum('total_weight_kg'),
                    'good_quantity' => $group->sum('good_quantity'),
                    'reject_quantity' => $group->sum('reject_quantity'),
                    'scrap_kg' => $group->sum('scrap_kg'),
                    'downtime_minutes' => $group->sum('downtime_minutes'),
                ];
            });

        return [
            'rows' => $rows,
            'dailyTotals' => $dailyTotals,
            'shifts' => $shifts,
            'summary' => [
                'declaration_count' => $declarations->count(),
                'machine_count' => $declarations->pluck('machine_id')->unique()->count(),
                'total_quantity' => $rows->sum('total_quantity'),
                'good_quantity' => $rows->sum('good_quantity'),
                'reject_quantity' => $rows->sum('reject_quantity'),
                'scrap_kg' => $rows->sum('scrap_kg'),
                'total_weight_kg' => $rows->sum('total_weight_kg'),
                'downtime_minutes' => $rows->sum('downtime_minutes'),
                'issue_count' => $rows->filter(fn (array $row) => !empty($row['issues']))->count(),
            ],
        ];
    }

    private function statuses(): array
    {
        return [
            'all_except_draft' => 'Đã gửi và đã phê duyệt',
            'approved' => 'Đã phê duyệt',
            'submitted' => 'Đã gửi',
            'draft' => 'Bản nháp',
            'all' => 'Tất cả trạng thái',
        ];
    }
}
