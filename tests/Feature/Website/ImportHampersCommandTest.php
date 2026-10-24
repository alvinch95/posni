<?php

namespace Tests\Feature\Website;

use App\Models\WebProduct;
use App\Models\WebProductOption;
use Illuminate\Support\Facades\Artisan;

class ImportHampersCommandTest extends WebsiteTestCase
{
    private const HEADERS = ['Serie', 'Hampers', 'Sudah di website', 'Akan dibuat'];

    /** Solar: one pair + one single; Kappa: one single; Item: ignored. */
    private function seedHampers(): array
    {
        $solar = $this->serie('Solar');
        $kappa = $this->serie('Kappa');
        $item = $this->serie('Item');

        $this->hamper('Solar F Grafir', 94000, [], null, $solar);
        $this->hamper('Solar F Tanpa Grafir', 89000, [], null, $solar);
        $this->hamper('Solar G', 99000, [], null, $solar);
        $this->hamper('Kappa A', 120000, [], null, $kappa);
        $this->hamper('Batu Kayu', 5000, [], null, $item);

        return [$solar, $kappa, $item];
    }

    /** @return array{0:int,1:string} exit code and output */
    private function importCommand(array $arguments = []): array
    {
        $code = Artisan::call('website:import-hampers', $arguments + ['--no-interaction' => true]);

        return [$code, Artisan::output()];
    }

    public function test_dry_run_prints_the_table_and_creates_nothing(): void
    {
        $this->seedHampers();

        $this->artisan('website:import-hampers', ['--dry-run' => true])
            ->expectsTable(self::HEADERS, [
                ['Kappa', 1, 0, 1],
                ['Solar', 3, 0, 2],
            ])
            ->expectsOutput('Total: 3 produk akan dibuat.')
            ->assertExitCode(0);

        $this->assertSame(0, WebProduct::count());
        $this->assertSame(0, WebProductOption::count());
    }

    public function test_dry_run_never_asks_for_confirmation(): void
    {
        $this->seedHampers();

        // No expectsConfirmation: an unexpected question would fail the run.
        $this->artisan('website:import-hampers', ['--dry-run' => true, '--publish' => true])->assertExitCode(0);

        $this->assertSame(0, WebProduct::count());
    }

    public function test_default_run_imports_every_serie_except_item_as_drafts(): void
    {
        $this->seedHampers();

        $this->artisan('website:import-hampers')
            ->expectsConfirmation('Buat 3 produk website sebagai draft?', 'yes')
            ->assertExitCode(0);

        $this->assertEqualsCanonicalizing(['Solar F', 'Solar G', 'Kappa A'], WebProduct::pluck('name_id')->all());
        $this->assertSame(0, WebProduct::where('is_published', true)->count());
        $this->assertSame(4, WebProductOption::count());
    }

    public function test_no_interaction_proceeds_because_the_default_answer_is_yes(): void
    {
        $this->seedHampers();

        [$code, $output] = $this->importCommand();

        $this->assertSame(0, $code);
        $this->assertSame(3, WebProduct::count());
        $this->assertStringContainsString('Selesai: 3 produk dibuat', $output);
    }

    public function test_declining_the_confirmation_creates_nothing(): void
    {
        $this->seedHampers();

        $this->artisan('website:import-hampers')
            ->expectsConfirmation('Buat 3 produk website sebagai draft?', 'no')
            ->assertExitCode(0);

        $this->assertSame(0, WebProduct::count());
    }

    public function test_serie_option_limits_the_import_by_name_or_id(): void
    {
        [$solar, $kappa] = $this->seedHampers();

        [$code] = $this->importCommand(['--serie' => ['kappa']]); // by name, any case
        $this->assertSame(0, $code);
        $this->assertSame(['Kappa A'], WebProduct::pluck('name_id')->all());

        [$code] = $this->importCommand(['--serie' => [(string) $solar]]); // by id
        $this->assertSame(0, $code);
        $this->assertEqualsCanonicalizing(['Kappa A', 'Solar F', 'Solar G'], WebProduct::pluck('name_id')->all());
    }

    public function test_serie_option_can_be_repeated(): void
    {
        [$solar, $kappa] = $this->seedHampers();

        [$code] = $this->importCommand(['--serie' => ['Solar', (string) $kappa]]);

        $this->assertSame(0, $code);
        $this->assertSame(3, WebProduct::count());
    }

    public function test_an_unknown_serie_exits_1_and_imports_nothing(): void
    {
        $this->seedHampers();

        [$code, $output] = $this->importCommand(['--serie' => ['Kappa', 'Nonexistent']]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Nonexistent', $output);
        $this->assertSame(0, WebProduct::count());
    }

    public function test_the_item_serie_cannot_be_imported(): void
    {
        $this->seedHampers();

        [$code, $output] = $this->importCommand(['--serie' => ['Item']]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Item', $output);
        $this->assertSame(0, WebProduct::count());
    }

    public function test_publish_publishes_what_it_imports(): void
    {
        $this->seedHampers();

        $this->artisan('website:import-hampers', ['--publish' => true])
            ->expectsConfirmation('Buat 3 produk website dan langsung publish?', 'yes')
            ->assertExitCode(0);

        $this->assertSame(3, WebProduct::where('is_published', true)->count());
        $this->assertSame(0, WebProduct::where('is_published', false)->count());
    }

    public function test_a_second_run_reports_nothing_new(): void
    {
        $this->seedHampers();
        $this->importCommand();

        [$code, $output] = $this->importCommand();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Tidak ada produk baru untuk diimport.', $output);
        $this->assertSame(3, WebProduct::count());
    }

    public function test_nothing_to_create_never_asks_for_confirmation(): void
    {
        $this->serie('Empty');

        $this->artisan('website:import-hampers')
            ->expectsOutput('Tidak ada produk baru untuk diimport.')
            ->assertExitCode(0);
    }

    public function test_the_table_shows_what_is_already_on_the_website(): void
    {
        [$solar, $kappa] = $this->seedHampers();
        $linked = \App\Models\Hamper::where('name', 'Kappa A')->firstOrFail();
        $this->webProduct(['slug' => 'kappa-manual'], ['engraved' => ['hamper_id' => $linked->id]]);

        $this->artisan('website:import-hampers', ['--dry-run' => true])
            ->expectsTable(self::HEADERS, [
                ['Kappa', 1, 1, 0],
                ['Solar', 3, 0, 2],
            ])
            ->assertExitCode(0);
    }

    public function test_the_summary_lists_the_skips(): void
    {
        [$solar] = $this->seedHampers();
        $this->hamper('Free A', 0, [], null, $solar);
        $linked = \App\Models\Hamper::where('name', 'Solar G')->firstOrFail();
        $this->webProduct(['slug' => 'solar-manual'], ['engraved' => ['hamper_id' => $linked->id]]);

        [$code, $output] = $this->importCommand();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('2 produk dibuat', $output);
        $this->assertStringContainsString('1 dilewati (sudah ada di website)', $output);
        $this->assertStringContainsString('1 hamper dilewati (harga kosong atau 0)', $output);
    }
}
