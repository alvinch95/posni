<?php

namespace App\Console\Commands;

use App\Services\Website\HamperImporter;
use Illuminate\Console\Command;

class ImportWebsiteProductsFromHampers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'website:import-hampers
                            {--serie=* : Serie name or id (repeatable). Default: all series except Item}
                            {--publish : Publish the imported products (only valid ones)}
                            {--dry-run : Show what would be created without saving anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create website products from posni hampers (a Grafir + Tanpa Grafir pair becomes one product), as drafts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(HamperImporter $importer)
    {
        $serieIds = null;
        $requested = array_map('strval', (array) $this->option('serie'));

        if ($requested !== []) {
            $serieIds = $this->resolveSeries($importer, $requested);

            if ($serieIds === null) {
                return 1; // already reported; nothing was imported
            }
        }

        $rows = $importer->preview($serieIds);
        $total = array_sum(array_column($rows, 'to_create'));

        $this->table(
            ['Serie', 'Hampers', 'Sudah di website', 'Akan dibuat'],
            array_map(fn (array $row) => [$row['serie'], $row['hampers'], $row['linked'], $row['to_create']], $rows)
        );
        $this->line("Total: {$total} produk akan dibuat.");

        if ($total === 0) {
            $this->info('Tidak ada produk baru untuk diimport.');

            return 0;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: tidak ada yang disimpan.');

            return 0;
        }

        $publish = (bool) $this->option('publish');
        $question = $publish
            ? "Buat {$total} produk website dan langsung publish?"
            : "Buat {$total} produk website sebagai draft?";

        if (! $this->confirm($question, true)) {
            $this->line('Dibatalkan, tidak ada yang disimpan.');

            return 0;
        }

        $result = $importer->import($serieIds, $publish);

        $this->info('Selesai: ' . HamperImporter::summarize($result, $publish) . '.');

        return 0;
    }

    /**
     * Turn the --serie values (a serie id or a name, any case) into serie ids. Reports every value
     * that matches no importable serie (Item never does) and returns null, so nothing runs.
     *
     * @param  array<int, string>  $values
     * @return array<int, int>|null
     */
    private function resolveSeries(HamperImporter $importer, array $values): ?array
    {
        $available = $importer->importableSeries();
        $ids = [];
        $unknown = [];

        foreach ($values as $value) {
            $value = trim($value);
            $matches = [];

            if (ctype_digit($value) && isset($available[(int) $value])) {
                $matches[] = (int) $value;
            } else {
                foreach ($available as $id => $name) {
                    if (mb_strtolower(trim($name)) === mb_strtolower($value)) {
                        $matches[] = (int) $id;
                    }
                }
            }

            if ($matches === []) {
                $unknown[] = $value;
            }

            $ids = array_merge($ids, $matches);
        }

        if ($unknown !== []) {
            $this->error('Serie tidak ditemukan: "' . implode('", "', $unknown) . '". Tidak ada yang diimport.');
            $this->line('Serie yang bisa diimport: ' . ($available === [] ? '(tidak ada)' : implode(', ', $available)) . '.');

            return null;
        }

        return array_values(array_unique($ids));
    }
}
