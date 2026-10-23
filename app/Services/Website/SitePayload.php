<?php

namespace App\Services\Website;

use App\Models\WebCategory;
use App\Models\WebFaq;
use App\Models\WebLink;
use App\Models\WebSetting;
use App\Models\WebTestimonial;
use Illuminate\Support\Collection;

/**
 * Builds the public content payloads (categories, testimonials, site). Like ProductCatalog, every
 * response is a literal array of allow-listed fields: models are never serialized.
 */
class SitePayload
{
    private const LINK_GROUPS = ['marketplace', 'social'];

    private const COPY_PREFIX = 'copy.';

    /**
     * Active categories with the number of published, valid products in each.
     *
     * @return array<int, array{slug:string, name:string, image:?string, productCount:int}>
     */
    public static function categories(string $locale): array
    {
        $validProductIds = ProductCatalog::validPublishedProductIds();

        $categories = WebCategory::query()
            ->where('is_active', true)
            ->withCount(['products as product_count' => fn ($products) => $products->whereIn('web_products.id', $validProductIds)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $rows = [];
        foreach ($categories as $category) {
            $rows[] = [
                'slug' => (string) $category->slug,
                'name' => Localized::pick($category->name_id, $category->name_en, $locale),
                'image' => MediaUrl::to($category->image),
                'productCount' => (int) $category->product_count,
            ];
        }

        return $rows;
    }

    /**
     * Active testimonials in display order.
     *
     * @return array<int, array{name:string, quote:string, occasion:string, photo:?string}>
     */
    public static function testimonials(string $locale): array
    {
        $rows = [];

        foreach (WebTestimonial::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get() as $testimonial) {
            $rows[] = [
                'name' => (string) $testimonial->name,
                'quote' => Localized::pick($testimonial->quote_id, $testimonial->quote_en, $locale),
                'occasion' => Localized::pick($testimonial->occasion_id, $testimonial->occasion_en, $locale),
                'photo' => MediaUrl::to($testimonial->photo),
            ];
        }

        return $rows;
    }

    /**
     * Contact details, links, FAQs and the homepage copy overrides.
     *
     * @return array{contact: array<string, mixed>, links: array<string, array<int, array<string, string>>>, faqs: array<int, array<string, string>>, copy: array<string, string>}
     */
    public static function build(string $locale): array
    {
        $settings = self::settings();

        return [
            'contact' => [
                // The number is a plain digits string with no EN variant: value_id only.
                'whatsappNumber' => self::settingValue($settings, 'contact.whatsapp_number', 'id'),
                'whatsappOrderTemplate' => self::settingValue($settings, 'contact.whatsapp_order_template', $locale) ?? '',
                'whatsappGeneralMessage' => self::settingValue($settings, 'contact.whatsapp_general_message', $locale) ?? '',
                'address' => self::settingValue($settings, 'contact.address', $locale) ?? '',
            ],
            'links' => self::links(),
            'faqs' => self::faqs($locale),
            'copy' => self::copy($settings, $locale),
        ];
    }

    /**
     * Only the settings rows the payload may expose: the four contact keys and "copy.<key>" for
     * every key defined in ContentKeys. Anything else in web_settings stays private.
     *
     * @return Collection<string, WebSetting>
     */
    private static function settings(): Collection
    {
        $keys = ['contact.whatsapp_number', 'contact.whatsapp_order_template', 'contact.whatsapp_general_message', 'contact.address'];
        foreach (array_keys(ContentKeys::all()) as $copyKey) {
            $keys[] = self::COPY_PREFIX . $copyKey;
        }

        return WebSetting::query()->whereIn('key', $keys)->get()->keyBy('key');
    }

    /**
     * @param  Collection<string, WebSetting>  $settings
     */
    private static function settingValue(Collection $settings, string $key, string $locale): ?string
    {
        $row = $settings->get($key);

        return $row === null ? null : $row->localized($locale);
    }

    /**
     * @param  Collection<string, WebSetting>  $settings
     * @return array<string, string>
     */
    private static function copy(Collection $settings, string $locale): array
    {
        $copy = [];

        foreach (array_keys(ContentKeys::all()) as $key) {
            $value = self::settingValue($settings, self::COPY_PREFIX . $key, $locale);
            if ($value !== null) {
                $copy[$key] = $value;
            }
        }

        return $copy;
    }

    /**
     * Active links, sorted, split into the two known groups.
     *
     * @return array{marketplace: array<int, array<string, string>>, social: array<int, array<string, string>>}
     */
    private static function links(): array
    {
        $links = ['marketplace' => [], 'social' => []];

        foreach (WebLink::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get() as $link) {
            if (! in_array($link->group, self::LINK_GROUPS, true)) {
                continue;
            }

            $links[$link->group][] = [
                'platform' => (string) $link->platform,
                'label' => (string) $link->label,
                'url' => (string) $link->url,
            ];
        }

        return $links;
    }

    /**
     * @return array<int, array{question:string, answer:string}>
     */
    private static function faqs(string $locale): array
    {
        $faqs = [];

        foreach (WebFaq::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get() as $faq) {
            $faqs[] = [
                'question' => Localized::pick($faq->question_id, $faq->question_en, $locale),
                'answer' => Localized::pick($faq->answer_id, $faq->answer_en, $locale),
            ];
        }

        return $faqs;
    }
}
