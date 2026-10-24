<?php

namespace Tests\Unit\Website;

use App\Services\Website\HamperImporter;
use PHPUnit\Framework\TestCase;

// Pure string parsing: no framework, no database.
class HamperImporterParseNameTest extends TestCase
{
    public function test_engraved_suffix_is_stripped(): void
    {
        $this->assertSame(['base' => 'Solar F', 'kind' => 'engraved'], HamperImporter::parseName('Solar F Grafir'));
    }

    public function test_plain_suffix_is_stripped(): void
    {
        $this->assertSame(['base' => 'Solar F', 'kind' => 'plain'], HamperImporter::parseName('Solar F Tanpa Grafir'));
    }

    public function test_parenthesis_variant_stays_in_the_base(): void
    {
        $this->assertSame(['base' => 'Miranda E (Putih)', 'kind' => 'engraved'], HamperImporter::parseName('Miranda E Grafir (Putih)'));
        $this->assertSame(['base' => 'Miranda E (Putih)', 'kind' => 'plain'], HamperImporter::parseName('Miranda E Tanpa Grafir (Putih)'));
    }

    public function test_single_name_hamper_defaults_to_engraved(): void
    {
        $this->assertSame(['base' => 'Kappa A', 'kind' => 'engraved'], HamperImporter::parseName('Kappa A'));
        $this->assertSame(
            ['base' => 'UPGRADE Gelas Kayu Stainless', 'kind' => 'engraved'],
            HamperImporter::parseName('UPGRADE Gelas Kayu Stainless')
        );
    }

    public function test_suffix_is_case_insensitive(): void
    {
        $this->assertSame(['base' => 'Solar F', 'kind' => 'engraved'], HamperImporter::parseName('Solar F grafir'));
        $this->assertSame(['base' => 'Solar F', 'kind' => 'engraved'], HamperImporter::parseName('Solar F GRAFIR'));
        $this->assertSame(['base' => 'Solar F', 'kind' => 'plain'], HamperImporter::parseName('Solar F tanpa grafir'));
        $this->assertSame(['base' => 'Solar F', 'kind' => 'plain'], HamperImporter::parseName('Solar F TANPA GRAFIR'));
    }

    public function test_surrounding_and_inner_whitespace_is_tolerated(): void
    {
        $this->assertSame(['base' => 'Solar F', 'kind' => 'engraved'], HamperImporter::parseName("  Solar F Grafir \t"));
        $this->assertSame(['base' => 'Solar F', 'kind' => 'plain'], HamperImporter::parseName("Solar F   Tanpa Grafir\n"));
        $this->assertSame(['base' => 'Kappa A', 'kind' => 'engraved'], HamperImporter::parseName('  Kappa A  '));
    }

    public function test_tanpa_grafir_is_not_mistaken_for_grafir(): void
    {
        // "Tanpa Grafir" ends in "Grafir"; the lazy base plus the alternation order must pick "plain".
        $this->assertSame('plain', HamperImporter::parseName('Aurora B Tanpa Grafir')['kind']);
        $this->assertSame('Aurora B', HamperImporter::parseName('Aurora B Tanpa Grafir')['base']);
    }

    public function test_a_name_that_is_only_the_suffix_is_kept_whole(): void
    {
        $this->assertSame(['base' => 'Grafir', 'kind' => 'engraved'], HamperImporter::parseName(' Grafir'));
        $this->assertSame(['base' => 'Tanpa Grafir', 'kind' => 'engraved'], HamperImporter::parseName(' Tanpa Grafir'));
    }

    public function test_grafir_in_the_middle_of_a_name_is_not_a_suffix(): void
    {
        // No whitespace-delimited suffix at the end: single-name default applies.
        $this->assertSame(['base' => 'Set Grafir Spesial', 'kind' => 'engraved'], HamperImporter::parseName('Set Grafir Spesial'));
    }
}
