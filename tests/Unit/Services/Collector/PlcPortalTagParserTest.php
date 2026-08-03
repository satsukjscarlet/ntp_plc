<?php

namespace Tests\Unit\Services\Collector;

use App\Services\Collector\PlcPortalTagParser;
use PHPUnit\Framework\TestCase;

class PlcPortalTagParserTest extends TestCase
{
    public function test_it_reads_siemens_watch_table_only(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
<table id="Language_Time_Date_Table">
    <tr><td>04:34:45 pm UTC PLC Local</td></tr>
</table>

<table class="Vartable" id="watch_table">
    <tr class="vartable_header">
        <td>Name</td>
        <td>Address</td>
        <td>Display Format</td>
        <td>Monitor Value</td>
        <td>Modify Value</td>
        <td></td>
        <td>Comment</td>
    </tr>
    <tr class="var">
        <td class="vartable_static_field readonly">&quot;Dừng khẩn cấp&quot;</td>
        <td class="vartable_static_field readonly">%I0.0</td>
        <td>
            <select class="type-selection">
                <option selected>BOOL</option>
                <option>Hex</option>
            </select>
        </td>
        <td id="vartable_updatable_0">
            <img src="/Icons/icontrue.gif"> true
        </td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    <tr class="var">
        <td class="vartable_static_field readonly">&quot;Nhiệt độ thực tế XL 1&quot;</td>
        <td class="vartable_static_field readonly">%MD1104</td>
        <td>
            <select class="type-selection">
                <option selected>Floating_Point</option>
                <option>Hex</option>
            </select>
        </td>
        <td id="vartable_updatable_1">33.5</td>
        <td></td>
        <td></td>
        <td>Zone XL 1</td>
    </tr>
</table>
</body>
</html>
HTML;

        $tags = (new PlcPortalTagParser())->parse($html);

        $this->assertCount(2, $tags);

        $this->assertSame('Dừng khẩn cấp', $tags[0]['source_name']);
        $this->assertSame('%I0.0', $tags[0]['address']);
        $this->assertSame('BOOL', $tags[0]['data_type']);
        $this->assertTrue($tags[0]['value']);

        $this->assertSame('Nhiệt độ thực tế XL 1', $tags[1]['source_name']);
        $this->assertSame('%MD1104', $tags[1]['address']);
        $this->assertSame('REAL', $tags[1]['data_type']);
        $this->assertSame('Floating_Point', $tags[1]['display_format']);
        $this->assertSame(33.5, $tags[1]['value']);
        $this->assertSame('Zone XL 1', $tags[1]['comment']);
    }
}
