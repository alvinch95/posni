<?php

namespace Tests\Feature\Website;

use App\Models\WebCategory;
use App\Models\WebFaq;
use App\Models\WebLink;
use App\Models\WebTestimonial;
use DOMDocument;
use DOMXPath;

/**
 * Every Website CMS page puts its sections in cards (the tinted card style lives under
 * body.cms-website in public/css/modern.css). This only checks the server-rendered structure;
 * how it looks at 320 to 1440 px is covered by the browser check.
 *
 * The phone list cards (_partials/list_card) are cards already, so a card inside a card means
 * somebody wrapped them again.
 */
class CmsCardsLayoutTest extends WebsiteTestCase
{
    /** @return array<string, array{0: string}> */
    public function pages(): array
    {
        return [
            'categories index' => ['categories.index'],
            'categories create' => ['categories.create'],
            'categories edit' => ['categories.edit'],
            'testimonials index' => ['testimonials.index'],
            'testimonials create' => ['testimonials.create'],
            'testimonials edit' => ['testimonials.edit'],
            'faqs index' => ['faqs.index'],
            'faqs create' => ['faqs.create'],
            'faqs edit' => ['faqs.edit'],
            'links create' => ['links.create'],
            'links edit' => ['links.edit'],
            'contact edit' => ['contact.edit'],
            'products import' => ['products.import'],
            'products index' => ['products.index'],
        ];
    }

    /** Fills every list with one row, so the index pages render their phone cards and their tables. */
    private function seedAndUrl(string $page): string
    {
        $category = WebCategory::create(['slug' => 'ulang-tahun', 'name_id' => 'Ulang Tahun']);
        $testimonial = WebTestimonial::create(['name' => 'Sari', 'quote_id' => 'Hampernya cantik sekali.']);
        $faq = WebFaq::create(['question_id' => 'Bisa grafir nama?', 'answer_id' => 'Bisa, gratis.']);
        $link = WebLink::create(['group' => 'marketplace', 'platform' => 'shopee', 'label' => 'Shopee Kayuni', 'url' => 'https://shopee.co.id/kayuni']);

        $serie = $this->serie('Solar');
        $hamper = $this->hamper('Solar F Grafir', 100000, [[$this->item('Kayu Solid', 5), 1]], null, $serie);
        $this->webProduct(['slug' => 'solar-f', 'name_id' => 'Solar F'], ['engraved' => ['hamper_id' => $hamper]]);

        $urls = [
            'categories.index' => route('website.categories.index'),
            'categories.create' => route('website.categories.create'),
            'categories.edit' => route('website.categories.edit', $category),
            'testimonials.index' => route('website.testimonials.index'),
            'testimonials.create' => route('website.testimonials.create'),
            'testimonials.edit' => route('website.testimonials.edit', $testimonial),
            'faqs.index' => route('website.faqs.index'),
            'faqs.create' => route('website.faqs.create'),
            'faqs.edit' => route('website.faqs.edit', $faq),
            'links.create' => route('website.links.create'),
            'links.edit' => route('website.links.edit', $link),
            'contact.edit' => route('website.contact.edit'),
            'products.import' => route('website.products.import'),
            'products.index' => route('website.products.index'),
        ];

        return $urls[$page];
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

    /** Whole-word class match for XPath. */
    private function cls(string $name): string
    {
        return "contains(concat(' ', normalize-space(@class), ' '), ' {$name} ')";
    }

    /**
     * @dataProvider pages
     */
    public function test_the_page_puts_its_sections_in_cards(string $page): void
    {
        $url = $this->seedAndUrl($page);
        $html = $this->actingAs($this->admin())->get($url)->assertOk()->getContent();
        $xpath = $this->xpath($html);

        $this->assertStringContainsString('class="card', $html, "{$page} renders no card.");

        // A field or table that sits straight on the page background is a section nobody wrapped.
        $card = 'ancestor::div[' . $this->cls('card') . ']';
        $loose = $xpath->query('//main//*[self::input[not(@type="hidden")] or self::select or self::textarea or self::table][not(' . $card . ')]');
        $names = [];
        foreach ($loose as $node) {
            $names[] = $node->nodeName . ($node->attributes->getNamedItem('name') ? '[' . $node->attributes->getNamedItem('name')->nodeValue . ']' : '');
        }
        $this->assertSame([], $names, "{$page} has fields or tables outside a card.");

        // The phone list cards are cards already, so no card may sit inside another card.
        $nested = $xpath->query('//main//div[' . $this->cls('card') . ']//div[' . $this->cls('card') . ']');
        $this->assertSame(0, $nested->length, "{$page} nests a card inside a card.");
    }

    /**
     * @dataProvider indexPages
     */
    public function test_the_desktop_table_card_is_hidden_on_phones(string $page, string $hiddenBelow): void
    {
        $url = $this->seedAndUrl($page);
        $xpath = $this->xpath($this->actingAs($this->admin())->get($url)->assertOk()->getContent());

        // The card, not only the table inside it, carries d-none d-{bp}-block: an empty card would show on a phone.
        $tableCards = $xpath->query('//main//div[' . $this->cls('card') . '][.//table]');
        $this->assertGreaterThanOrEqual(1, $tableCards->length, "{$page} has no card around its table.");
        foreach ($tableCards as $tableCard) {
            $classes = ' ' . preg_replace('/\s+/', ' ', $tableCard->attributes->getNamedItem('class')->nodeValue) . ' ';
            $this->assertStringContainsString(' d-none ', $classes, "{$page}: the table card must be hidden on phones.");
            $this->assertStringContainsString(" d-{$hiddenBelow}-block ", $classes, "{$page}: the table card must show from {$hiddenBelow} up.");
        }
    }

    /** @return array<string, array{0: string, 1: string}> */
    public function indexPages(): array
    {
        return [
            'categories index' => ['categories.index', 'md'],
            'testimonials index' => ['testimonials.index', 'md'],
            'faqs index' => ['faqs.index', 'md'],
            'products index' => ['products.index', 'lg'],
        ];
    }

    public function test_the_products_filters_and_bulk_bar_sit_in_a_card_that_stays_visible_on_phones(): void
    {
        $xpath = $this->xpath($this->actingAs($this->admin())->get($this->seedAndUrl('products.index'))->assertOk()->getContent());

        // Search, sort and "Pilih semua" are needed on a phone too, so their card must not be the d-none table card.
        foreach (['//input[@name="search"]', '//input[@id="wp-select-all"]'] as $field) {
            $cards = $xpath->query('//main' . $field . '/ancestor::div[' . $this->cls('card') . ']');
            $this->assertSame(1, $cards->length, "{$field} must sit in a card.");
            $classes = $cards->item(0)->attributes->getNamedItem('class')->nodeValue;
            $this->assertStringNotContainsString('d-none', $classes, "{$field} must stay visible on phones.");
        }
    }

    public function test_contact_has_a_kontak_card_and_a_links_card(): void
    {
        $xpath = $this->xpath($this->actingAs($this->admin())->get($this->seedAndUrl('contact.edit'))->assertOk()->getContent());

        foreach (['Kontak', 'Link marketplace & sosial media'] as $title) {
            $headings = $xpath->query('//main//div[' . $this->cls('card') . ']//h2[normalize-space()="' . $title . '"]');
            $this->assertSame(1, $headings->length, "{$title} is a heading inside its own card.");
        }

        // The WhatsApp number is a Kontak field, the links table belongs to the links card.
        $this->assertSame(1, $xpath->query('//main//div[' . $this->cls('card') . '][.//h2[normalize-space()="Kontak"]]//input[@name="whatsapp_number"]')->length);
        $this->assertSame(0, $xpath->query('//main//div[' . $this->cls('card') . '][.//h2[normalize-space()="Kontak"]]//table')->length);
        $this->assertGreaterThanOrEqual(1, $xpath->query('//main//div[' . $this->cls('card') . '][.//h2[normalize-space()="Link marketplace & sosial media"]]//table')->length);
    }

    /**
     * @dataProvider formPages
     */
    public function test_a_form_keeps_its_buttons_and_gets_a_publikasi_card(string $page): void
    {
        $xpath = $this->xpath($this->actingAs($this->admin())->get($this->seedAndUrl($page))->assertOk()->getContent());

        $this->assertSame(1, $xpath->query('//main//form//button[@type="submit"]')->length);
        $this->assertSame(1, $xpath->query('//main//div[' . $this->cls('card') . '][.//h2[normalize-space()="Publikasi"]]//input[@name="sort_order"]')->length, "{$page}: urutan and aktif go in a Publikasi card.");
        $this->assertSame(1, $xpath->query('//main//div[' . $this->cls('card') . '][.//h2[normalize-space()="Publikasi"]]//input[@name="is_active"][@type="checkbox"]')->length);
    }

    /** @return array<string, array{0: string}> */
    public function formPages(): array
    {
        return [
            'categories create' => ['categories.create'],
            'categories edit' => ['categories.edit'],
            'testimonials create' => ['testimonials.create'],
            'testimonials edit' => ['testimonials.edit'],
            'faqs create' => ['faqs.create'],
            'faqs edit' => ['faqs.edit'],
            'links create' => ['links.create'],
            'links edit' => ['links.edit'],
        ];
    }
}
