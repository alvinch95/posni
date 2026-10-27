<?php

namespace Tests\Feature\Website;

use App\Models\WebSetting;
use App\Services\Website\ContentKeys;

class ContentAdminTest extends WebsiteTestCase
{
    private const BAD_CHARS = 'Karakter { } < > tidak didukung.';

    /** The sections the screen is made of: section key => [label, keys in order, textarea keys]. */
    private const EXPECTED = [
        'hero' => ['Hero', ['Hero.eyebrow', 'Hero.headlineLine1', 'Hero.headlineLine2Prefix', 'Hero.headlineAccent', 'Hero.headlineSuffix', 'Hero.sub', 'Hero.ctaPrimary', 'Hero.ctaSecondary'], ['Hero.sub']],
        'trust' => ['Trust strip', ['Trust.sameDayTitle', 'Trust.sameDaySub', 'Trust.engraveTitle', 'Trust.engraveSub', 'Trust.cardTitle', 'Trust.cardSub', 'Trust.shipTitle', 'Trust.shipSub'], []],
        'hero_visual' => ['Foto hero', ['HeroVisual.alt', 'HeroVisual.bestseller', 'HeroVisual.productName', 'HeroVisual.engraveBadgeTitle', 'HeroVisual.engraveBadgeSub', 'HeroVisual.untukLabel', 'HeroVisual.recipientName', 'HeroVisual.materialBadgeTitle', 'HeroVisual.materialBadgeSub'], ['HeroVisual.alt']],
        'top_products' => ['Hampers Pilihan', ['TopProducts.sectionEyebrow', 'TopProducts.sectionTitle', 'TopProducts.sectionSub'], []],
        'categories' => ['Kategori', ['ShopByCategory.sectionEyebrow', 'ShopByCategory.sectionTitle', 'ShopByCategory.sectionSub'], []],
        'cara_pesan' => ['Cara Pesan', ['CaraPesan.sectionEyebrow', 'CaraPesan.sectionTitle', 'CaraPesan.sectionSub', 'CaraPesan.step1Title', 'CaraPesan.step1Desc', 'CaraPesan.step2Title', 'CaraPesan.step2Desc', 'CaraPesan.step3Title', 'CaraPesan.step3Desc', 'CaraPesan.step4Title', 'CaraPesan.step4Desc'], ['CaraPesan.step1Desc', 'CaraPesan.step2Desc', 'CaraPesan.step3Desc', 'CaraPesan.step4Desc']],
        'custom_cta' => ['CTA Custom', ['CustomCTA.title', 'CustomCTA.desc', 'CustomCTA.ctaWa', 'CustomCTA.ctaCustom'], ['CustomCTA.desc']],
        'testimonials' => ['Testimoni (judul)', ['Testimonials.sectionEyebrow', 'Testimonials.sectionTitle', 'Testimonials.sectionSub'], []],
        'faq' => ['FAQ (judul)', ['Faq.sectionEyebrow', 'Faq.sectionTitle', 'Faq.sectionSub'], []],
        'koleksi' => ['Halaman Koleksi', ['KoleksiPage.pageEyebrow', 'KoleksiPage.pageTitle', 'KoleksiPage.pageSub'], ['KoleksiPage.pageSub']],
        'footer' => ['Footer', ['Footer.tagline'], []],
    ];

    /** What the browser posts when the form is saved with every input blank (the page posts all fields every time). */
    private function blankForm(): array
    {
        $copy = [];
        foreach (array_keys(ContentKeys::all()) as $key) {
            $copy[str_replace('.', '__', $key)] = ['id' => '', 'en' => ''];
        }

        return ['copy' => $copy];
    }

    private function formWith(string $field, ?string $id, ?string $en = ''): array
    {
        $form = $this->blankForm();
        $form['copy'][$field] = ['id' => $id, 'en' => $en];

        return $form;
    }

    // ---- access ----

    public function test_staff_get_403(): void
    {
        // guests first: no actingAs yet
        $this->get(route('website.content.edit'))->assertForbidden();
        $this->put(route('website.content.update'), $this->formWith('Hero__eyebrow', 'Hack'))->assertForbidden();

        $this->actingAs($this->staff());

        $this->get(route('website.content.edit'))->assertForbidden();
        $this->put(route('website.content.update'), $this->formWith('Hero__eyebrow', 'Hack'))->assertForbidden();

        $this->assertSame(0, WebSetting::count());
    }

    public function test_routes_live_under_the_website_admin_prefix(): void
    {
        $this->assertSame(url('/dashboard/website/content'), route('website.content.edit'));
        $this->assertSame(url('/dashboard/website/content'), route('website.content.update'));
    }

    // ---- config ----

    public function test_config_has_the_agreed_sections_keys_and_textareas(): void
    {
        $sections = ContentKeys::sections();

        $this->assertSame(array_keys(self::EXPECTED), array_keys($sections));

        foreach (self::EXPECTED as $section => [$label, $keys, $textareas]) {
            $this->assertSame($label, $sections[$section]['label'], "label of {$section}");
            $this->assertSame($keys, array_keys($sections[$section]['keys']), "keys of {$section}");

            foreach ($sections[$section]['keys'] as $key => $field) {
                $this->assertSame(in_array($key, $textareas, true) ? 'textarea' : 'text', $field['type'], "type of {$key}");
                $this->assertNotSame('', trim($field['label']), "label of {$key}");
                $this->assertNotSame('', $field['default_id'], "default_id of {$key}");
                $this->assertNotSame('', $field['default_en'], "default_en of {$key}");
            }
        }

        // The defaults are what the frontend ships, so they can never contain what the CMS rejects.
        foreach (ContentKeys::all() as $key => $field) {
            $this->assertSame(0, preg_match('/[{}<>]/', $field['default_id'] . $field['default_en']), "ICU/tag characters in the defaults of {$key}");
        }
    }

    // ---- edit page ----

    public function test_edit_page_lists_every_section_and_default_placeholder(): void
    {
        $response = $this->actingAs($this->admin())->get(route('website.content.edit'));

        $response->assertOk()->assertSee('Grafir nama custom');

        foreach (self::EXPECTED as [$label, $keys]) {
            $response->assertSee($label);

            foreach ($keys as $key) {
                $field = str_replace('.', '__', $key);
                $response->assertSee("copy[{$field}][id]", false);
                $response->assertSee("copy[{$field}][en]", false);
            }
        }

        // Both languages' defaults are the placeholders.
        $response->assertSee('placeholder="Hampers kayu untuk setiap momen"', false);
        $response->assertSee('placeholder="Wooden hampers for every moment"', false);
    }

    public function test_a_saved_value_is_prefilled_and_other_fields_stay_empty(): void
    {
        WebSetting::put('copy.Hero.eyebrow', 'Tersimpan', 'Saved');
        WebSetting::put('copy.Hero.sub', 'Sub tersimpan');

        $response = $this->actingAs($this->admin())->get(route('website.content.edit'));

        $response->assertOk();
        $response->assertSee('name="copy[Hero__eyebrow][id]"', false);
        $response->assertSee('value="Tersimpan"', false);
        $response->assertSee('value="Saved"', false);
        $response->assertSee('>Sub tersimpan</textarea>', false);

        // A key nobody saved has no prefilled value, only the placeholder.
        $response->assertDontSee('value="Lihat Koleksi"', false);
        $response->assertSee('placeholder="Lihat Koleksi"', false);
    }

    // ---- saving ----

    public function test_saving_stores_only_non_empty_values(): void
    {
        $this->actingAs($this->admin())
            ->put(route('website.content.update'), $this->formWith('Hero__eyebrow', 'Baru'))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('website.content.edit'));

        $this->assertSame(1, WebSetting::count());

        $row = WebSetting::first();
        $this->assertSame('copy.Hero.eyebrow', $row->key);
        $this->assertSame('Baru', $row->value_id);
        $this->assertNull($row->value_en);
    }

    public function test_saving_stores_both_languages_trimmed_and_allows_symbols(): void
    {
        $this->actingAs($this->admin())
            ->put(route('website.content.update'), $this->formWith('Trust__shipSub', '  JNE, J&T, "SiCepat" ♡  ', ' JNE, J&T '))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, WebSetting::count());
        $this->assertSame('JNE, J&T, "SiCepat" ♡', WebSetting::valueFor('copy.Trust.shipSub', 'id'));
        $this->assertSame('JNE, J&T', WebSetting::valueFor('copy.Trust.shipSub', 'en'));
    }

    public function test_english_only_value_is_kept(): void
    {
        $this->actingAs($this->admin())
            ->put(route('website.content.update'), $this->formWith('Hero__eyebrow', '', 'Only EN'))
            ->assertSessionHasNoErrors();

        $row = WebSetting::where('key', 'copy.Hero.eyebrow')->first();
        $this->assertNotNull($row);
        $this->assertNull($row->value_id);
        $this->assertSame('Only EN', $row->value_en);
    }

    public function test_saving_replaces_the_previous_value(): void
    {
        WebSetting::put('copy.Hero.eyebrow', 'Lama', 'Old');

        $this->actingAs($this->admin())
            ->put(route('website.content.update'), $this->formWith('Hero__eyebrow', 'Baru', 'New'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, WebSetting::count());
        $this->assertSame('Baru', WebSetting::valueFor('copy.Hero.eyebrow', 'id'));
        $this->assertSame('New', WebSetting::valueFor('copy.Hero.eyebrow', 'en'));
    }

    public function test_clearing_a_value_deletes_it(): void
    {
        WebSetting::put('copy.Hero.eyebrow', 'Akan dihapus', 'To delete');
        WebSetting::put('copy.Footer.tagline', 'Tetap ada');

        // The form posts every field; the eyebrow is now blank, the tagline is posted back unchanged.
        $form = $this->formWith('Footer__tagline', 'Tetap ada');

        $this->actingAs($this->admin())
            ->put(route('website.content.update'), $form)
            ->assertSessionHasNoErrors();

        $this->assertNull(WebSetting::where('key', 'copy.Hero.eyebrow')->first());
        $this->assertSame(1, WebSetting::count());
        $this->assertSame('Tetap ada', WebSetting::valueFor('copy.Footer.tagline', 'id'));
    }

    public function test_whitespace_only_value_counts_as_cleared(): void
    {
        WebSetting::put('copy.Hero.eyebrow', 'Ada');

        $this->actingAs($this->admin())
            ->put(route('website.content.update'), $this->formWith('Hero__eyebrow', '   ', "\t"))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, WebSetting::count());
    }

    public function test_unknown_keys_are_ignored(): void
    {
        $form = $this->formWith('Hero__eyebrow', 'Baru');
        $form['copy']['Bogus__key'] = ['id' => 'Tidak boleh', 'en' => 'Nope'];
        $form['copy']['Hero__tidakAda'] = ['id' => 'Tidak boleh', 'en' => 'Nope'];
        $form['copy']['Hero.eyebrow'] = ['id' => 'Pakai titik', 'en' => 'Dotted'];
        $form['copy']['Bad__{key}'] = ['id' => 'Karakter {}', 'en' => '<b>x</b>'];
        $form['contact'] = ['whatsapp_number' => '6281234567890'];

        $this->actingAs($this->admin())
            ->put(route('website.content.update'), $form)
            ->assertSessionHasNoErrors();

        $this->assertSame(['copy.Hero.eyebrow'], WebSetting::pluck('key')->all());
        $this->assertSame('Baru', WebSetting::valueFor('copy.Hero.eyebrow', 'id'));
    }

    public function test_only_submitted_copy_keys_are_touched(): void
    {
        WebSetting::put('contact.address', 'Jl. Kayu 1');
        WebSetting::put('copy.Footer.tagline', 'Tidak dikirim');
        WebSetting::put('copy.Hero.eyebrow', 'Akan diganti');

        // A request that carries only one key (or no copy map at all) must not wipe the rest.
        $this->actingAs($this->admin())
            ->put(route('website.content.update'), ['copy' => ['Hero__eyebrow' => ['id' => 'Baru']]])
            ->assertSessionHasNoErrors();

        $this->assertSame('Baru', WebSetting::valueFor('copy.Hero.eyebrow', 'id'));
        $this->assertSame('Tidak dikirim', WebSetting::valueFor('copy.Footer.tagline', 'id'));

        $this->put(route('website.content.update'), [])->assertSessionHasNoErrors();

        $this->assertSame(3, WebSetting::count());
        $this->assertSame('Jl. Kayu 1', WebSetting::valueFor('contact.address', 'id'));
    }

    // ---- validation (Review Focus 3) ----

    public function test_copy_with_braces_or_tags_is_rejected(): void
    {
        $this->actingAs($this->admin());

        foreach (['Promo {spesial}' => 'copy.Hero__eyebrow.id', '<b>hi</b>' => 'copy.Hero__eyebrow.id'] as $value => $errorKey) {
            $this->put(route('website.content.update'), $this->formWith('Hero__eyebrow', $value))
                ->assertSessionHasErrors([$errorKey => self::BAD_CHARS]);
        }

        // Every one of the four characters, in either language.
        foreach (['{', '}', '<', '>'] as $char) {
            $this->put(route('website.content.update'), $this->formWith('Footer__tagline', 'ok', "a {$char} b"))
                ->assertSessionHasErrors(['copy.Footer__tagline.en' => self::BAD_CHARS]);
        }

        $this->assertSame(0, WebSetting::count());
    }

    public function test_a_rejected_save_stores_nothing_even_for_the_valid_fields(): void
    {
        $form = $this->formWith('Hero__eyebrow', 'Valid');
        $form['copy']['Footer__tagline'] = ['id' => 'Promo {spesial}', 'en' => ''];

        $this->actingAs($this->admin())
            ->put(route('website.content.update'), $form)
            ->assertSessionHasErrors(['copy.Footer__tagline.id' => self::BAD_CHARS]);

        $this->assertSame(0, WebSetting::count());
    }

    public function test_rejected_input_comes_back_on_the_page_with_the_message(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('website.content.edit'))
            ->put(route('website.content.update'), $this->formWith('Hero__eyebrow', 'Promo {spesial}'))
            ->assertRedirect(route('website.content.edit'));

        $this->actingAs($admin)
            ->get(route('website.content.edit'))
            ->assertOk()
            ->assertSee(self::BAD_CHARS)
            ->assertSee('value="Promo {spesial}"', false)
            ->assertSee('is-invalid', false);
    }

    public function test_value_length_is_capped_at_1000_characters(): void
    {
        $this->actingAs($this->admin());

        $this->put(route('website.content.update'), $this->formWith('Hero__sub', str_repeat('a', 1001)))
            ->assertSessionHasErrors('copy.Hero__sub.id');
        $this->assertSame(0, WebSetting::count());

        $this->put(route('website.content.update'), $this->formWith('Hero__sub', str_repeat('a', 1000)))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, WebSetting::count());
    }
}
