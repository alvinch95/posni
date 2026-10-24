<?php

namespace Tests\Feature\Website;

use App\Models\WebCategory;
use App\Models\WebProductImage;
use DOMDocument;
use DOMXPath;

/**
 * Server-rendered parts of the Produk Website form (create + edit): the two-column layout, the
 * ID | EN tab sets, the hamper & price panels and the publish warning. The live behaviour (price
 * preview, tab switching, sticky sidebar) is covered by the browser check, not here.
 */
class ProductFormViewTest extends WebsiteTestCase
{
    private function stockedHamper(string $name, int $price = 100000): int
    {
        return $this->hamper($name, $price, [[$this->item("Item {$name}", 5), 1]]);
    }

    private function xpath(string $html): DOMXPath
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    private function createHtml(): string
    {
        return $this->actingAs($this->admin())->get(route('website.products.create'))->assertOk()->getContent();
    }

    private function editHtml($product): string
    {
        return $this->actingAs($this->admin())->get(route('website.products.edit', $product))->assertOk()->getContent();
    }

    /** The attribute of the first node an expression matches, or null when nothing matches. */
    private function attr(DOMXPath $xpath, string $expression, string $attribute): ?string
    {
        $node = $xpath->query($expression)->item(0);

        return $node && $node->attributes->getNamedItem($attribute) ? $node->attributes->getNamedItem($attribute)->nodeValue : null;
    }

    private function hasAttr(DOMXPath $xpath, string $expression, string $attribute): bool
    {
        $node = $xpath->query($expression)->item(0);
        $this->assertNotNull($node, "Nothing matches {$expression}");

        return $node->attributes->getNamedItem($attribute) !== null;
    }

    /** Whole-word class match for XPath. */
    private function cls(string $name): string
    {
        return "contains(concat(' ', normalize-space(@class), ' '), ' {$name} ')";
    }

    public function test_create_and_edit_render_a_main_column_and_a_right_sidebar(): void
    {
        $engraved = $this->stockedHamper('Solar F Grafir');
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F'], ['engraved' => ['hamper_id' => $engraved]]);

        foreach ([$this->createHtml(), $this->editHtml($product)] as $html) {
            $xpath = $this->xpath($html);

            $main = $xpath->query('//form[@id="product-form"]//div[' . $this->cls('col-lg-8') . ']');
            $side = $xpath->query('//form[@id="product-form"]//div[' . $this->cls('col-lg-4') . ']');
            $this->assertSame(1, $main->length);
            $this->assertSame(1, $side->length);

            // Left column holds the three content cards, the sidebar holds the other three.
            foreach (['Hamper & harga', 'Info produk', 'Deskripsi & isi paket'] as $title) {
                $this->assertSame(1, $xpath->query('//div[' . $this->cls('col-lg-8') . ']//h2[normalize-space()="' . $title . '"]')->length, "{$title} belongs to the main column");
            }
            foreach (['Publikasi', 'Kategori', 'Foto'] as $title) {
                $this->assertSame(1, $xpath->query('//div[' . $this->cls('col-lg-4') . ']//h2[normalize-space()="' . $title . '"]')->length, "{$title} belongs to the sidebar");
            }

            // The sidebar sticks on lg and up (class hook; the rule itself is in the page styles).
            $this->assertStringContainsString('product-form-side', $html);
        }
    }

    public function test_sidebar_order_is_phone_first_then_publikasi_on_top_for_desktop(): void
    {
        $xpath = $this->xpath($this->createHtml());

        $titles = [];
        foreach ($xpath->query('//div[' . $this->cls('col-lg-4') . ']//h2') as $heading) {
            $titles[] = trim($heading->textContent);
        }
        // DOM order = phone order (Kategori, Foto, Publikasi); lg lifts Publikasi to the top with an order class.
        $this->assertSame(['Kategori', 'Foto', 'Publikasi'], $titles);
        $this->assertTrue($xpath->query('//div[' . $this->cls('col-lg-4') . ']//h2[normalize-space()="Publikasi"]/ancestor::div[' . $this->cls('card') . '][1][' . $this->cls('order-lg-first') . ']')->length === 1);
    }

    public function test_new_copy_replaces_the_old_labels(): void
    {
        $engraved = $this->stockedHamper('Solar F Grafir');
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F'], ['engraved' => ['hamper_id' => $engraved, 'price_override' => 90000]]);
        WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/x.jpg', 'sort_order' => 3]);

        foreach ([$this->createHtml(), $this->editHtml($product)] as $html) {
            foreach ([
                'Hamper &amp; harga', 'Dengan grafir nama', 'Tanpa grafir', 'Harga di website', 'Ikut harga posni',
                'Kosongkan = ikut harga posni', 'Tampil di website', 'Tidak dijual', 'Info produk', 'Pengaturan lanjutan',
                'Deskripsi &amp; isi paket', 'Isi paket', 'Isi dari posni', 'Publikasi', 'Urutan', 'Simpan', 'Batal',
                'Kategori', 'Foto', 'Tanpa foto → pakai foto hamper', 'Unggulan homepage', 'Bestseller',
            ] as $text) {
                $this->assertStringContainsString($text, $html, "Missing copy: {$text}");
            }

            foreach (['Harga khusus', 'Pilihan hamper', 'Foto produk', 'Isi hamper (ID)'] as $gone) {
                $this->assertStringNotContainsString($gone, $html, "Old copy still there: {$gone}");
            }
        }
    }

    public function test_every_input_name_is_rendered_exactly_once(): void
    {
        $engraved = $this->stockedHamper('Solar F Grafir');
        $this->category('a');
        $this->category('b');
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F'], ['engraved' => ['hamper_id' => $engraved]]);
        $image = WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/x.jpg', 'sort_order' => 3]);

        $names = [
            'name_id', 'name_en', 'slug', 'description_id', 'description_en', 'contents_id', 'contents_en', 'sort_order',
            'options[engraved][hamper_id]', 'options[engraved][price_override]',
            'options[plain][hamper_id]', 'options[plain][price_override]',
            'images[]', "image_order[{$image->id}]", 'remove_images[]',
        ];

        $html = $this->editHtml($product);
        foreach ($names as $name) {
            $this->assertSame(1, substr_count($html, 'name="' . $name . '"'), "name=\"{$name}\" must appear exactly once");
        }

        // One checkbox per category, one hidden + one checkbox per toggle, nothing doubled.
        $this->assertSame(2, substr_count($html, 'name="categories[]"'));
        $xpath = $this->xpath($html);
        foreach (['is_published', 'is_bestseller', 'is_featured'] as $toggle) {
            $this->assertSame(1, $xpath->query("//input[@type='hidden'][@name='{$toggle}']")->length, "{$toggle} hidden");
            $this->assertSame(1, $xpath->query("//input[@type='checkbox'][@name='{$toggle}']")->length, "{$toggle} checkbox");
        }

        // The create page too (no images yet).
        $create = $this->createHtml();
        foreach (array_diff($names, ["image_order[{$image->id}]", 'remove_images[]']) as $name) {
            $this->assertSame(1, substr_count($create, 'name="' . $name . '"'), "create: name=\"{$name}\" must appear exactly once");
        }
    }

    public function test_tab_sets_are_accessible_and_id_is_active_and_required_by_default(): void
    {
        $xpath = $this->xpath($this->createHtml());

        foreach (['name-tabs', 'desc-tabs'] as $id) {
            $this->assertSame('tablist', $this->attr($xpath, "//div[@id='{$id}']//ul", 'role'));

            $this->assertSame('tab', $this->attr($xpath, "//button[@id='{$id}-tab-id']", 'role'));
            $this->assertSame('true', $this->attr($xpath, "//button[@id='{$id}-tab-id']", 'aria-selected'));
            $this->assertSame("{$id}-pane-id", $this->attr($xpath, "//button[@id='{$id}-tab-id']", 'aria-controls'));
            $this->assertSame('false', $this->attr($xpath, "//button[@id='{$id}-tab-en']", 'aria-selected'));
            $this->assertSame("{$id}-pane-en", $this->attr($xpath, "//button[@id='{$id}-tab-en']", 'aria-controls'));

            $this->assertSame('tabpanel', $this->attr($xpath, "//div[@id='{$id}-pane-id']", 'role'));
            $this->assertSame("{$id}-tab-id", $this->attr($xpath, "//div[@id='{$id}-pane-id']", 'aria-labelledby'));
            $this->assertSame(1, $xpath->query("//div[@id='{$id}-pane-id'][" . $this->cls('active') . ']')->length);
            $this->assertSame(0, $xpath->query("//div[@id='{$id}-pane-en'][" . $this->cls('active') . ']')->length);
        }

        // Only the Nama tab set has a required Indonesian field.
        $this->assertSame(1, $xpath->query("//button[@id='name-tabs-tab-id']//*[" . $this->cls('text-danger') . ']')->length);
        $this->assertSame(0, $xpath->query("//button[@id='desc-tabs-tab-id']//*[" . $this->cls('text-danger') . ']')->length);

        // The fields live in their panes.
        $this->assertSame(1, $xpath->query("//div[@id='name-tabs-pane-id']//input[@name='name_id']")->length);
        $this->assertSame(1, $xpath->query("//div[@id='name-tabs-pane-en']//input[@name='name_en']")->length);
        foreach (['description', 'contents'] as $field) {
            $this->assertSame(1, $xpath->query("//div[@id='desc-tabs-pane-id']//textarea[@name='{$field}_id']")->length);
            $this->assertSame(1, $xpath->query("//div[@id='desc-tabs-pane-en']//textarea[@name='{$field}_en']")->length);
        }

        // "Isi dari posni" sits in the Indonesian pane, next to the Isi paket label.
        $this->assertSame(1, $xpath->query("//div[@id='desc-tabs-pane-id']//button[@id='fill-contents']")->length);
    }

    public function test_en_tab_shows_a_dot_only_when_an_english_field_has_a_value(): void
    {
        $plain = $this->stockedHamper('Solar F Tanpa Grafir');
        $with = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F', 'name_en' => 'Solar F EN'], ['plain' => ['hamper_id' => $plain]]);
        $without = $this->webProduct(['slug' => 'black-d', 'name_id' => 'Black D', 'contents_en' => "Wooden box\nCard"], ['plain' => ['hamper_id' => $plain]]);

        $xpath = $this->xpath($this->editHtml($with));
        $this->assertFalse($this->hasAttr($xpath, "//button[@id='name-tabs-tab-en']//*[" . $this->cls('lang-dot') . ']', 'hidden'), 'name EN has a value: dot shown');
        $this->assertTrue($this->hasAttr($xpath, "//button[@id='desc-tabs-tab-en']//*[" . $this->cls('lang-dot') . ']', 'hidden'), 'no description/contents EN: no dot');

        // One filled field is enough for a tab set that covers two.
        $xpath = $this->xpath($this->editHtml($without));
        $this->assertTrue($this->hasAttr($xpath, "//button[@id='name-tabs-tab-en']//*[" . $this->cls('lang-dot') . ']', 'hidden'));
        $this->assertFalse($this->hasAttr($xpath, "//button[@id='desc-tabs-tab-en']//*[" . $this->cls('lang-dot') . ']', 'hidden'));

        // Fresh create form: no dots at all.
        $xpath = $this->xpath($this->createHtml());
        $this->assertTrue($this->hasAttr($xpath, "//button[@id='name-tabs-tab-en']//*[" . $this->cls('lang-dot') . ']', 'hidden'));
        $this->assertTrue($this->hasAttr($xpath, "//button[@id='desc-tabs-tab-en']//*[" . $this->cls('lang-dot') . ']', 'hidden'));
    }

    public function test_old_input_restores_values_and_drives_the_dot(): void
    {
        $html = $this->withSession(['_old_input' => ['name_id' => 'Lama', 'name_en' => 'Old EN', 'description_en' => 'Old desc']])
            ->actingAs($this->admin())
            ->get(route('website.products.create'))
            ->assertOk()
            ->getContent();
        $xpath = $this->xpath($html);

        $this->assertSame('Lama', $this->attr($xpath, "//input[@name='name_id']", 'value'));
        $this->assertSame('Old EN', $this->attr($xpath, "//input[@name='name_en']", 'value'));
        $this->assertSame('Old desc', trim($xpath->query("//textarea[@name='description_en']")->item(0)->textContent));
        $this->assertFalse($this->hasAttr($xpath, "//button[@id='name-tabs-tab-en']//*[" . $this->cls('lang-dot') . ']', 'hidden'));
        $this->assertFalse($this->hasAttr($xpath, "//button[@id='desc-tabs-tab-en']//*[" . $this->cls('lang-dot') . ']', 'hidden'));
        // Values alone do not switch the tab.
        $this->assertSame('true', $this->attr($xpath, "//button[@id='name-tabs-tab-id']", 'aria-selected'));
    }

    public function test_en_tab_is_active_after_an_english_only_validation_error(): void
    {
        $admin = $this->admin();

        // name_en too long, name_id fine: the Nama set opens on EN, the Deskripsi set stays on ID.
        $html = $this->actingAs($admin)->from(route('website.products.create'))->followingRedirects()
            ->post(route('website.products.store'), ['name_id' => 'Solar F', 'name_en' => str_repeat('x', 256), 'sort_order' => 0])
            ->assertOk()
            ->getContent();
        $xpath = $this->xpath($html);

        $this->assertSame('true', $this->attr($xpath, "//button[@id='name-tabs-tab-en']", 'aria-selected'));
        $this->assertSame('false', $this->attr($xpath, "//button[@id='name-tabs-tab-id']", 'aria-selected'));
        $this->assertSame(1, $xpath->query("//div[@id='name-tabs-pane-en'][" . $this->cls('active') . ']')->length);
        $this->assertSame('true', $this->attr($xpath, "//button[@id='desc-tabs-tab-id']", 'aria-selected'));

        // The message is under the field, with is-invalid, as before.
        $this->assertStringContainsString('Nama (EN) maksimal 255 karakter.', $html);
        $this->assertSame(1, $xpath->query("//input[@name='name_en'][" . $this->cls('is-invalid') . ']')->length);
        $this->assertSame(1, $xpath->query("//input[@name='name_en']/following-sibling::div[" . $this->cls('invalid-feedback') . ']')->length);
        // The oversized value is restored (old input), so its dot is on too.
        $this->assertFalse($this->hasAttr($xpath, "//button[@id='name-tabs-tab-en']//*[" . $this->cls('lang-dot') . ']', 'hidden'));
    }

    public function test_description_set_opens_on_en_when_only_an_english_field_failed(): void
    {
        $html = $this->actingAs($this->admin())->from(route('website.products.create'))->followingRedirects()
            ->post(route('website.products.store'), ['name_id' => 'Solar F', 'contents_en' => str_repeat('x', 5001), 'sort_order' => 0])
            ->assertOk()
            ->getContent();
        $xpath = $this->xpath($html);

        $this->assertSame('true', $this->attr($xpath, "//button[@id='desc-tabs-tab-en']", 'aria-selected'));
        $this->assertSame('true', $this->attr($xpath, "//button[@id='name-tabs-tab-id']", 'aria-selected'));
        $this->assertStringContainsString('Isi hamper (EN) maksimal 5000 karakter.', $html);
    }

    public function test_id_tab_wins_when_both_languages_have_errors_or_only_the_id_one_does(): void
    {
        $admin = $this->admin();

        // Both failed: the Indonesian one is the required one, show it first.
        $xpath = $this->xpath($this->actingAs($admin)->from(route('website.products.create'))->followingRedirects()
            ->post(route('website.products.store'), ['name_id' => str_repeat('x', 256), 'name_en' => str_repeat('x', 256), 'sort_order' => 0])
            ->assertOk()
            ->getContent());
        $this->assertSame('true', $this->attr($xpath, "//button[@id='name-tabs-tab-id']", 'aria-selected'));

        // Only name_id failed (empty): ID.
        $xpath = $this->xpath($this->actingAs($admin)->from(route('website.products.create'))->followingRedirects()
            ->post(route('website.products.store'), ['name_id' => '', 'name_en' => 'Fine', 'sort_order' => 0])
            ->assertOk()
            ->getContent());
        $this->assertSame('true', $this->attr($xpath, "//button[@id='name-tabs-tab-id']", 'aria-selected'));
    }

    public function test_en_tab_is_active_for_session_errors_too(): void
    {
        $errors = (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag(['name_en' => ['Nama (EN) tidak valid.']]));

        $xpath = $this->xpath($this->withSession(['errors' => $errors])->actingAs($this->admin())->get(route('website.products.create'))->assertOk()->getContent());

        $this->assertSame('true', $this->attr($xpath, "//button[@id='name-tabs-tab-en']", 'aria-selected'));
    }

    public function test_slug_sits_in_a_closed_collapse_that_opens_on_a_slug_error(): void
    {
        $xpath = $this->xpath($this->createHtml());

        $this->assertSame(1, $xpath->query("//div[@id='slug-advanced'][" . $this->cls('collapse') . "][not(" . $this->cls('show') . ")]//input[@name='slug']")->length);
        $this->assertSame('false', $this->attr($xpath, "//button[@data-bs-target='#slug-advanced']", 'aria-expanded'));
        $this->assertSame('slug-advanced', $this->attr($xpath, "//button[@data-bs-target='#slug-advanced']", 'aria-controls'));
        $this->assertSame('collapse', $this->attr($xpath, "//button[@data-bs-target='#slug-advanced']", 'data-bs-toggle'));
        $this->assertStringContainsString('Pengaturan lanjutan', $this->createHtml());

        // A duplicate slug fails validation: the collapse is open so the message is visible.
        $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F']);
        $html = $this->actingAs($this->admin())->from(route('website.products.create'))->followingRedirects()
            ->post(route('website.products.store'), ['name_id' => 'Solar F', 'slug' => 'solar-f', 'sort_order' => 0])
            ->assertOk()
            ->getContent();
        $xpath = $this->xpath($html);

        $this->assertSame(1, $xpath->query("//div[@id='slug-advanced'][" . $this->cls('collapse') . '][' . $this->cls('show') . "]//input[@name='slug'][" . $this->cls('is-invalid') . ']')->length);
        $this->assertSame('true', $this->attr($xpath, "//button[@data-bs-target='#slug-advanced']", 'aria-expanded'));
        $this->assertStringContainsString('Slug sudah dipakai produk lain.', $html);
    }

    public function test_hamper_panels_show_the_not_sold_badge_until_a_hamper_is_picked(): void
    {
        $engraved = $this->stockedHamper('Solar F Grafir');
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F'], ['engraved' => ['hamper_id' => $engraved, 'price_override' => 90000]]);

        // Create: nothing picked, both badges visible, preview says a dash.
        $xpath = $this->xpath($this->createHtml());
        foreach (['engraved', 'plain'] as $kind) {
            $this->assertFalse($this->hasAttr($xpath, "//div[@data-kind='{$kind}']//*[" . $this->cls('not-sold') . ']', 'hidden'));
            $this->assertSame('—', trim($xpath->query("//div[@data-kind='{$kind}']//*[" . $this->cls('price-preview-value') . ']')->item(0)->textContent));
        }

        // Edit: only the empty kind is "Tidak dijual".
        $xpath = $this->xpath($this->editHtml($product));
        $this->assertTrue($this->hasAttr($xpath, "//div[@data-kind='engraved']//*[" . $this->cls('not-sold') . ']', 'hidden'));
        $this->assertFalse($this->hasAttr($xpath, "//div[@data-kind='plain']//*[" . $this->cls('not-sold') . ']', 'hidden'));

        // Panels, titles and the price input per panel.
        $this->assertSame('Dengan grafir nama', trim($xpath->query("//div[@data-kind='engraved']//h3")->item(0)->textContent));
        $this->assertSame('Tanpa grafir', trim($xpath->query("//div[@data-kind='plain']//h3")->item(0)->textContent));
        $this->assertSame('Ikut harga posni', $this->attr($xpath, "//input[@name='options[engraved][price_override]']", 'placeholder'));
        $this->assertSame('90000', $this->attr($xpath, "//input[@name='options[engraved][price_override]']", 'value'));
        $this->assertSame(1, $xpath->query("//div[" . $this->cls('col-md-6') . "]/div[@data-kind='engraved']")->length);
        $this->assertSame(1, $xpath->query("//div[" . $this->cls('col-md-6') . "]/div[@data-kind='plain']")->length);
    }

    public function test_publish_warning_is_rendered_open_only_when_published_without_any_hamper(): void
    {
        $plain = $this->stockedHamper('Solar F Tanpa Grafir');
        $published = $this->webProduct(['slug' => 'live', 'name_id' => 'Live', 'is_published' => true], ['plain' => ['hamper_id' => $plain]]);
        $orphan = $this->webProduct(['slug' => 'orphan', 'name_id' => 'Orphan', 'is_published' => true]);
        $draft = $this->webProduct(['slug' => 'draft', 'name_id' => 'Draft', 'is_published' => false]);

        $warning = "//*[@id='publish-warning']";

        $xpath = $this->xpath($this->editHtml($published));
        $this->assertTrue($this->hasAttr($xpath, $warning, 'hidden'));

        $xpath = $this->xpath($this->editHtml($orphan));
        $this->assertFalse($this->hasAttr($xpath, $warning, 'hidden'));
        $this->assertSame('Belum bisa tampil: pilih minimal 1 hamper', trim($xpath->query($warning)->item(0)->textContent));

        $xpath = $this->xpath($this->editHtml($draft));
        $this->assertTrue($this->hasAttr($xpath, $warning, 'hidden'));

        $xpath = $this->xpath($this->createHtml());
        $this->assertTrue($this->hasAttr($xpath, $warning, 'hidden'));
    }

    public function test_save_buttons_and_the_phone_bar_submit_the_same_form(): void
    {
        $xpath = $this->xpath($this->createHtml());

        // Desktop: Simpan + Batal in the Publikasi card.
        $this->assertSame(1, $xpath->query("//form[@id='product-form']//div[" . $this->cls('product-form-side') . "]//button[@type='submit'][normalize-space()='Simpan']")->length);
        // Phone: a fixed bar with its own Simpan, shown below lg only, inside the same form.
        $this->assertSame(1, $xpath->query("//form[@id='product-form']//div[" . $this->cls('product-save-bar') . "]//button[@type='submit'][normalize-space()='Simpan']")->length);
        $this->assertSame(1, $xpath->query("//div[" . $this->cls('product-save-bar') . '][' . $this->cls('d-lg-none') . ']')->length);
        $this->assertSame(2, $xpath->query("//form[@id='product-form']//button[@type='submit']")->length);
    }

    public function test_categories_list_is_a_checkbox_list_that_scrolls_inside_its_card(): void
    {
        $birthday = $this->category('birthday');
        $this->category('wedding', 1, false);
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F']);
        $product->categories()->attach($birthday->id);

        $xpath = $this->xpath($this->editHtml($product));

        $this->assertSame(2, $xpath->query("//div[" . $this->cls('col-lg-4') . "]//input[@type='checkbox'][@name='categories[]']")->length);
        $this->assertSame(1, $xpath->query("//input[@name='categories[]'][@value='{$birthday->id}'][@checked]")->length);
        $this->assertSame(1, $xpath->query("//*[" . $this->cls('category-list') . ']')->length);
        $this->assertStringContainsString('(nonaktif)', $this->editHtml($product));
    }

    public function test_images_render_as_a_thumbnail_grid_with_order_and_remove_per_tile(): void
    {
        $product = $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F']);
        $first = WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/a.jpg', 'sort_order' => 0]);
        $second = WebProductImage::create(['web_product_id' => $product->id, 'path' => 'website/products/b.jpg', 'sort_order' => 1]);

        $html = $this->editHtml($product);
        $xpath = $this->xpath($html);

        $this->assertSame(2, $xpath->query("//div[" . $this->cls('photo-grid') . "]//div[" . $this->cls('photo-tile') . ']')->length);
        foreach ([$first, $second] as $image) {
            $this->assertSame(1, $xpath->query("//div[" . $this->cls('photo-tile') . "]//input[@name='image_order[{$image->id}]']")->length);
            $this->assertSame(1, $xpath->query("//div[" . $this->cls('photo-tile') . "]//input[@name='remove_images[]'][@value='{$image->id}']")->length);
        }
        $this->assertSame(1, $xpath->query("//input[@type='file'][@name='images[]'][@multiple]")->length);
        $this->assertStringContainsString('Hapus foto', $html);
        $this->assertStringContainsString('Tanpa foto → pakai foto hamper', $html);
    }

    public function test_form_text_is_escaped(): void
    {
        $evil = '"><script>alert(1)</script>';
        $this->category('x')->update(['name_id' => $evil]);
        $product = $this->webProduct(['slug' => 'xss', 'name_id' => $evil, 'name_en' => $evil, 'description_en' => $evil, 'contents_id' => $evil]);
        $product->categories()->attach(WebCategory::first()->id);

        $html = $this->editHtml($product);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    private function category(string $slug = 'birthday', int $sort = 0, bool $active = true): WebCategory
    {
        return WebCategory::create(['slug' => $slug, 'name_id' => ucfirst($slug), 'sort_order' => $sort, 'is_active' => $active]);
    }
}
