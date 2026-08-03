<?php

namespace App\Console\Commands;

use App\Models\Plc;
use App\Services\Collector\PlcPortalTagParser;
use App\Services\Collector\TagSynchronizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class SyncPlcTagsCommand extends Command
{
    protected $signature = 'plc:sync-tags
        {plc : PLC ID, code hoặc name}
        {--dump : Lưu HTML tải được vào storage/app/plc-debug}
        {--preview : Chỉ phân tích, không ghi database}';

    protected $description = 'Đọc PLC Portal và đồng bộ tag vào database';

    public function handle(
        PlcPortalTagParser $parser,
        TagSynchronizationService $synchronizer
    ): int {
        $plc = $this->findPlc((string) $this->argument('plc'));

        if ($plc === null) {
            $this->error('Không tìm thấy PLC.');
            return self::FAILURE;
        }

        if (!$plc->collector_url) {
            $this->error('PLC chưa được cấu hình collector_url.');
            return self::FAILURE;
        }

        try {
            $this->info('Đang tải PLC Portal: ' . $plc->collector_url);

            $response = Http::connectTimeout(10)
                ->timeout(60)
                ->retry(2, 1000)
                ->withHeaders([
                    'Accept' => 'text/html,application/xhtml+xml',
                    'User-Agent' => 'NTP_PLC Collector/1.0',
                ])
                ->get($plc->collector_url);

            $response->throw();

            $html = $response->body();

            if ($this->option('dump')) {
                $this->dumpHtml($plc->id, $html);
            }

            $tags = $parser->parse($html);

            if ($tags === []) {
                $this->warn('Parser không tìm thấy tag hợp lệ.');
                return self::FAILURE;
            }

            $this->info('Số tag parser nhận được: ' . count($tags));

            $this->table(
                ['Address', 'Source name', 'Data type', 'Unit'],
                array_map(
                    fn (array $tag): array => [
                        $tag['address'] ?: 'NAME-ONLY',
                        $tag['source_name'],
                        $tag['data_type'] ?: '-',
                        $tag['unit'] ?: '-',
                    ],
                    array_slice($tags, 0, 20)
                )
            );

            if (count($tags) > 20) {
                $this->line('Chỉ hiển thị 20 tag đầu tiên.');
            }

            if ($this->option('preview')) {
                $this->info('Preview hoàn tất, chưa ghi database.');
                return self::SUCCESS;
            }

            $result = $synchronizer->synchronize($plc, $tags);

            $this->newLine();
            $this->table(
                [
                    'Received',
                    'New',
                    'Updated',
                    'Matched',
                    'Removed',
                    'Skipped',
                    'Duplicate',
                    'Duration',
                ],
                [[
                    $result['total_received'],
                    $result['new'],
                    $result['updated'],
                    $result['matched'],
                    $result['removed'],
                    $result['skipped'],
                    $result['duplicate'],
                    $result['duration_ms'] . ' ms',
                ]]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);

            $this->error($exception->getMessage());

            if ($this->output->isVerbose()) {
                $this->line($exception->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    private function findPlc(string $value): ?Plc
    {
        return Plc::query()
            ->where('id', $value)
            ->orWhere('name', $value)
            ->when(
                \Schema::hasColumn('plcs', 'code'),
                fn ($query) => $query->orWhere('code', $value)
            )
            ->first();
    }

    private function dumpHtml(int $plcId, string $html): void
    {
        $directory = storage_path('app/plc-debug');

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $file = sprintf(
            '%s/plc-%d-%s.html',
            $directory,
            $plcId,
            now()->format('Ymd-His')
        );

        file_put_contents($file, $html);

        $this->line('Đã lưu HTML debug: ' . $file);
    }
}
