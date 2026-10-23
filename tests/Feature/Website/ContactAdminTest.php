<?php

namespace Tests\Feature\Website;

use App\Models\WebLink;
use App\Models\WebSetting;

class ContactAdminTest extends WebsiteTestCase
{
    private const INVALID_NUMBER = 'Nomor WhatsApp tidak valid. Contoh: 6281234567890';

    private const DEFAULT_TEMPLATE = 'Halo Kayuni! Aku mau pesan {product} ({option}) — {price}. {url}';

    private function contactPayload(array $override = []): array
    {
        return $override + [
            'whatsapp_number' => '0812-3456-7890',
            'whatsapp_order_template_id' => self::DEFAULT_TEMPLATE,
            'whatsapp_order_template_en' => 'Hi Kayuni! I would like to order {product} ({option}) — {price}. {url}',
            'whatsapp_general_message_id' => 'Halo Kayuni, mau tanya dulu ya.',
            'whatsapp_general_message_en' => 'Hi Kayuni, I have a question.',
            'address_id' => 'Jl. Contoh No. 1, Jakarta',
            'address_en' => 'Example St. 1, Jakarta',
        ];
    }

    private function linkPayload(array $override = []): array
    {
        return $override + [
            'group' => 'marketplace',
            'platform' => 'shopee',
            'label' => 'Shopee Kayuni',
            'url' => 'https://shopee.co.id/kayuni',
            'sort_order' => 1,
            'is_active' => '1',
        ];
    }

    private function link(array $attrs = []): WebLink
    {
        return WebLink::create($attrs + [
            'group' => 'marketplace',
            'platform' => 'shopee',
            'label' => 'Shopee Kayuni',
            'url' => 'https://shopee.co.id/kayuni',
        ]);
    }

    // ---- access ----

    public function test_staff_get_403(): void
    {
        $link = $this->link();

        $calls = [
            ['get', route('website.contact.edit'), []],
            ['put', route('website.contact.update'), $this->contactPayload()],
            ['get', route('website.links.create'), []],
            ['post', route('website.links.store'), $this->linkPayload()],
            ['get', route('website.links.edit', $link), []],
            ['put', route('website.links.update', $link), $this->linkPayload(['label' => 'Hack'])],
            ['delete', route('website.links.destroy', $link), []],
        ];

        // guests first: no actingAs yet
        foreach ($calls as [$method, $url, $data]) {
            $this->call($method, $url, $data)->assertForbidden();
        }

        $staff = $this->staff();
        foreach ($calls as [$method, $url, $data]) {
            $this->actingAs($staff)->call($method, $url, $data)->assertForbidden();
        }

        $this->assertSame(0, WebSetting::count());
        $this->assertSame(1, WebLink::count());
        $this->assertSame('Shopee Kayuni', $link->fresh()->label);
    }

    // ---- contact settings ----

    public function test_admin_saves_normalized_number_and_templates(): void
    {
        $this->actingAs($this->admin())
            ->put(route('website.contact.update'), $this->contactPayload())
            ->assertRedirect(route('website.contact.edit'))
            ->assertSessionHasNoErrors();

        $number = WebSetting::where('key', 'contact.whatsapp_number')->firstOrFail();
        $this->assertSame('6281234567890', $number->value_id);
        $this->assertNull($number->value_en);

        $order = WebSetting::where('key', 'contact.whatsapp_order_template')->firstOrFail();
        $this->assertSame(self::DEFAULT_TEMPLATE, $order->value_id);
        $this->assertSame('Hi Kayuni! I would like to order {product} ({option}) — {price}. {url}', $order->value_en);

        $general = WebSetting::where('key', 'contact.whatsapp_general_message')->firstOrFail();
        $this->assertSame('Halo Kayuni, mau tanya dulu ya.', $general->value_id);
        $this->assertSame('Hi Kayuni, I have a question.', $general->value_en);

        $address = WebSetting::where('key', 'contact.address')->firstOrFail();
        $this->assertSame('Jl. Contoh No. 1, Jakarta', $address->value_id);
        $this->assertSame('Example St. 1, Jakarta', $address->value_en);

        $this->assertSame(4, WebSetting::count());
    }

    public function test_saved_values_are_trimmed(): void
    {
        $this->actingAs($this->admin())
            ->put(route('website.contact.update'), $this->contactPayload([
                'whatsapp_general_message_id' => '  Halo, mau tanya.  ',
                'whatsapp_general_message_en' => "\tHi there.\n",
            ]))
            ->assertSessionHasNoErrors();

        $general = WebSetting::where('key', 'contact.whatsapp_general_message')->firstOrFail();
        $this->assertSame('Halo, mau tanya.', $general->value_id);
        $this->assertSame('Hi there.', $general->value_en);
    }

    public function test_edit_page_shows_normalized_number_placeholders_help_and_default_template(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('website.contact.update'), $this->contactPayload())
            ->assertRedirect(route('website.contact.edit'));

        $page = $this->actingAs($admin)->get(route('website.contact.edit'))->assertOk();
        $page->assertSee('value="6281234567890"', false);
        $page->assertSee('Jl. Contoh No. 1, Jakarta');
        // the help text lists every placeholder the frontend fills in
        $page->assertSee('{product}', false);
        $page->assertSee('{option}', false);
        $page->assertSee('{price}', false);
        $page->assertSee('{url}', false);

        // an unset template falls back to the frontend default, shown as the input placeholder
        WebSetting::where('key', 'contact.whatsapp_order_template')->delete();
        $this->actingAs($admin)->get(route('website.contact.edit'))
            ->assertOk()
            ->assertSee('placeholder="' . self::DEFAULT_TEMPLATE . '"', false);
    }

    public function test_invalid_number_shows_error(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('website.contact.edit'))
            ->put(route('website.contact.update'), $this->contactPayload(['whatsapp_number' => '12345']))
            ->assertRedirect(route('website.contact.edit'))
            ->assertSessionHasErrors(['whatsapp_number' => self::INVALID_NUMBER]);

        // nothing is saved when validation fails
        $this->assertSame(0, WebSetting::count());

        // and the page shows the message next to the field, keeping what was typed
        $this->actingAs($admin)
            ->followingRedirects()
            ->from(route('website.contact.edit'))
            ->put(route('website.contact.update'), $this->contactPayload(['whatsapp_number' => 'abc']))
            ->assertSee(self::INVALID_NUMBER)
            ->assertSee('value="abc"', false);
    }

    public function test_clearing_number_removes_setting(): void
    {
        $admin = $this->admin();
        WebSetting::put('contact.whatsapp_number', '6281234567890');

        $this->actingAs($admin)
            ->put(route('website.contact.update'), $this->contactPayload(['whatsapp_number' => '']))
            ->assertRedirect(route('website.contact.edit'))
            ->assertSessionHasNoErrors();

        $this->assertNull(WebSetting::where('key', 'contact.whatsapp_number')->first());
        // the other fields are unaffected
        $this->assertNotNull(WebSetting::where('key', 'contact.address')->first());
    }

    public function test_clearing_text_fields_removes_their_settings(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put(route('website.contact.update'), $this->contactPayload());
        $this->assertSame(4, WebSetting::count());

        $this->actingAs($admin)
            ->put(route('website.contact.update'), [
                'whatsapp_number' => '',
                'whatsapp_order_template_id' => '',
                'whatsapp_order_template_en' => '',
                'whatsapp_general_message_id' => '   ',
                'whatsapp_general_message_en' => '',
                'address_id' => '',
                'address_en' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, WebSetting::count());
    }

    public function test_contact_length_limits(): void
    {
        $admin = $this->admin();

        $cases = [
            ['whatsapp_order_template_id' => str_repeat('a', 501)],
            ['whatsapp_order_template_en' => str_repeat('a', 501)],
            ['whatsapp_general_message_id' => str_repeat('a', 501)],
            ['whatsapp_general_message_en' => str_repeat('a', 501)],
            ['address_id' => str_repeat('a', 256)],
            ['address_en' => str_repeat('a', 256)],
        ];
        foreach ($cases as $override) {
            $this->actingAs($admin)
                ->put(route('website.contact.update'), $override + $this->contactPayload())
                ->assertSessionHasErrors(array_key_first($override));
        }
        $this->assertSame(0, WebSetting::count());

        // exactly at the limits is fine
        $this->actingAs($admin)
            ->put(route('website.contact.update'), $this->contactPayload([
                'whatsapp_order_template_id' => str_repeat('a', 500),
                'whatsapp_general_message_id' => str_repeat('b', 500),
                'address_id' => str_repeat('c', 255),
            ]))
            ->assertSessionHasNoErrors();
        $this->assertSame(4, WebSetting::count());
    }

    // ---- links ----

    public function test_admin_adds_marketplace_link_and_it_appears_in_site_api(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('website.contact.update'), $this->contactPayload())
            ->assertRedirect(route('website.contact.edit'));

        $this->actingAs($admin)
            ->post(route('website.links.store'), $this->linkPayload())
            ->assertRedirect(route('website.contact.edit'))
            ->assertSessionHasNoErrors();

        $link = WebLink::firstOrFail();
        $this->assertSame('marketplace', $link->group);
        $this->assertSame('shopee', $link->platform);
        $this->assertSame('Shopee Kayuni', $link->label);
        $this->assertSame('https://shopee.co.id/kayuni', $link->url);
        $this->assertSame(1, $link->sort_order);
        $this->assertTrue($link->is_active);

        $site = $this->getJson('/api/website/v1/site')->assertOk();
        $site->assertJsonPath('data.contact.whatsappNumber', '6281234567890');
        $site->assertJsonPath('data.links.marketplace.0', [
            'platform' => 'shopee',
            'label' => 'Shopee Kayuni',
            'url' => 'https://shopee.co.id/kayuni',
        ]);
        $this->assertSame([], $site->json('data.links.social'));
    }

    public function test_admin_adds_social_link_with_defaults(): void
    {
        $this->actingAs($this->admin())
            ->post(route('website.links.store'), [
                'group' => 'social',
                'platform' => 'instagram',
                'label' => '@kayuni.id',
                'url' => 'https://instagram.com/kayuni.id',
            ])
            ->assertRedirect(route('website.contact.edit'))
            ->assertSessionHasNoErrors();

        $link = WebLink::firstOrFail();
        $this->assertSame(0, $link->sort_order);
        $this->assertTrue($link->is_active);

        $this->getJson('/api/website/v1/site')
            ->assertOk()
            ->assertJsonPath('data.links.social.0.platform', 'instagram');
    }

    public function test_admin_updates_and_deletes_link(): void
    {
        $admin = $this->admin();
        $link = $this->link();

        $this->actingAs($admin)->get(route('website.links.edit', $link))->assertOk()->assertSee('Shopee Kayuni');

        $this->actingAs($admin)
            ->put(route('website.links.update', $link), $this->linkPayload([
                'group' => 'social',
                'platform' => 'tiktok',
                'label' => 'TikTok Kayuni',
                'url' => 'https://www.tiktok.com/@kayuni',
                'sort_order' => 7,
                'is_active' => '0',
            ]))
            ->assertRedirect(route('website.contact.edit'))
            ->assertSessionHasNoErrors();

        $link = $link->fresh();
        $this->assertSame('social', $link->group);
        $this->assertSame('tiktok', $link->platform);
        $this->assertSame('TikTok Kayuni', $link->label);
        $this->assertSame('https://www.tiktok.com/@kayuni', $link->url);
        $this->assertSame(7, $link->sort_order);
        $this->assertFalse($link->is_active);

        // an inactive link is hidden from the public API
        $this->assertSame([], $this->getJson('/api/website/v1/site')->json('data.links.social'));

        $this->actingAs($admin)
            ->delete(route('website.links.destroy', $link))
            ->assertRedirect(route('website.contact.edit'));
        $this->assertSame(0, WebLink::count());
    }

    public function test_contact_page_lists_links(): void
    {
        $this->link(['label' => 'Shopee Kayuni']);
        $this->link(['group' => 'social', 'platform' => 'instagram', 'label' => '@kayuni.id', 'url' => 'https://instagram.com/kayuni.id']);

        $this->actingAs($this->admin())
            ->get(route('website.contact.edit'))
            ->assertOk()
            ->assertSee('Shopee Kayuni')
            ->assertSee('@kayuni.id')
            ->assertSee('https://instagram.com/kayuni.id')
            ->assertSee(route('website.links.create'), false);
    }

    public function test_invalid_link_url_rejected(): void
    {
        $admin = $this->admin();

        // Laravel's `url` rule alone accepts the non-web schemes (ftp, file, data, ...): only http(s) may be a public href.
        $badUrls = [
            'bukan url',
            'shopee.co.id/kayuni',
            'javascript:alert(1)',
            'ftp://x.com/a',
            'file://localhost/etc/passwd',
            'data://text/html,hello',
            '',
        ];
        foreach ($badUrls as $url) {
            $this->actingAs($admin)
                ->post(route('website.links.store'), $this->linkPayload(['url' => $url]))
                ->assertSessionHasErrors('url');
        }

        $this->assertSame(0, WebLink::count());
    }

    public function test_link_field_rules(): void
    {
        $admin = $this->admin();

        $cases = [
            ['group' => 'other'],
            ['group' => ''],
            ['platform' => 'facebook'],
            ['platform' => ''],
            ['label' => ''],
            ['label' => str_repeat('a', 51)],
            ['url' => 'https://example.com/' . str_repeat('a', 240)],
            ['sort_order' => -1],
            ['sort_order' => 'abc'],
            ['sort_order' => 65536],
        ];
        foreach ($cases as $override) {
            $this->actingAs($admin)
                ->post(route('website.links.store'), $override + $this->linkPayload())
                ->assertSessionHasErrors(array_key_first($override));
        }
        $this->assertSame(0, WebLink::count());

        // every allowed platform, and the limits themselves, are accepted
        foreach (['shopee', 'tokopedia', 'toco', 'instagram', 'tiktok', 'other'] as $platform) {
            $this->actingAs($admin)
                ->post(route('website.links.store'), $this->linkPayload(['platform' => $platform, 'group' => 'social', 'label' => str_repeat('a', 50), 'sort_order' => 65535]))
                ->assertSessionHasNoErrors();
        }
        $this->assertSame(6, WebLink::count());
    }
}
