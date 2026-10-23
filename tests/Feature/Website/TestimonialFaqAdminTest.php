<?php

namespace Tests\Feature\Website;

use App\Models\WebCategory;
use App\Models\WebFaq;
use App\Models\WebTestimonial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TestimonialFaqAdminTest extends WebsiteTestCase
{
    private function testimonial(array $attrs = []): WebTestimonial
    {
        static $n = 0;
        $n++;

        return WebTestimonial::create($attrs + [
            'name' => "Pelanggan {$n}",
            'quote_id' => "Kutipan {$n}",
        ]);
    }

    private function faq(array $attrs = []): WebFaq
    {
        static $n = 0;
        $n++;

        return WebFaq::create($attrs + [
            'question_id' => "Pertanyaan {$n}?",
            'answer_id' => "Jawaban {$n}.",
        ]);
    }

    // ---- access ----

    public function test_staff_get_403_on_both(): void
    {
        $urls = [
            ['get', route('website.testimonials.index')],
            ['get', route('website.testimonials.create')],
            ['post', route('website.testimonials.store')],
            ['get', route('website.faqs.index')],
            ['get', route('website.faqs.create')],
            ['post', route('website.faqs.store')],
        ];

        // guests first: no actingAs yet
        foreach ($urls as [$method, $url]) {
            $this->call($method, $url, ['name' => 'X', 'quote_id' => 'X', 'question_id' => 'X', 'answer_id' => 'X'])->assertForbidden();
        }

        $staff = $this->staff();
        foreach ($urls as [$method, $url]) {
            $this->actingAs($staff)
                ->call($method, $url, ['name' => 'X', 'quote_id' => 'X', 'question_id' => 'X', 'answer_id' => 'X'])
                ->assertForbidden();
        }

        $testimonial = $this->testimonial();
        $faq = $this->faq();
        $this->actingAs($staff)->get(route('website.testimonials.edit', $testimonial))->assertForbidden();
        $this->actingAs($staff)->put(route('website.testimonials.update', $testimonial), ['name' => 'Hack', 'quote_id' => 'Hack'])->assertForbidden();
        $this->actingAs($staff)->delete(route('website.testimonials.destroy', $testimonial))->assertForbidden();
        $this->actingAs($staff)->get(route('website.faqs.edit', $faq))->assertForbidden();
        $this->actingAs($staff)->put(route('website.faqs.update', $faq), ['question_id' => 'Hack', 'answer_id' => 'Hack'])->assertForbidden();
        $this->actingAs($staff)->delete(route('website.faqs.destroy', $faq))->assertForbidden();

        $this->assertSame(1, WebTestimonial::count());
        $this->assertSame(1, WebFaq::count());
        $this->assertNotSame('Hack', $faq->fresh()->question_id);
        $this->assertNotSame('Hack', $testimonial->fresh()->name);
    }

    // ---- testimonials ----

    public function test_admin_creates_and_updates_testimonial_with_photo(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('website.testimonials.store'), [
                'name' => 'Sinta',
                'quote_id' => 'Hampernya cantik banget!',
                'quote_en' => 'The hamper is gorgeous!',
                'occasion_id' => 'Ulang Tahun',
                'occasion_en' => 'Birthday',
                'sort_order' => 2,
                'is_active' => '1',
                'photo' => UploadedFile::fake()->image('sinta.jpg'),
            ])
            ->assertRedirect(route('website.testimonials.index'))
            ->assertSessionHasNoErrors();

        $row = WebTestimonial::firstOrFail();
        $this->assertSame('Sinta', $row->name);
        $this->assertSame('Hampernya cantik banget!', $row->quote_id);
        $this->assertSame('The hamper is gorgeous!', $row->quote_en);
        $this->assertSame('Ulang Tahun', $row->occasion_id);
        $this->assertSame('Birthday', $row->occasion_en);
        $this->assertSame(2, $row->sort_order);
        $this->assertTrue($row->is_active);
        $this->assertStringStartsWith('website/testimonials/', $row->photo);
        Storage::disk('public')->assertExists($row->photo);

        // update: new photo replaces the old file, other fields change
        $oldPhoto = $row->photo;
        $this->actingAs($admin)
            ->put(route('website.testimonials.update', $row), [
                'name' => 'Sinta Dewi',
                'quote_id' => 'Sudah dipakai buat kado farewell.',
                'quote_en' => '',
                'occasion_id' => 'Farewell',
                'occasion_en' => '',
                'sort_order' => 5,
                'is_active' => '0',
                'photo' => UploadedFile::fake()->image('sinta2.png'),
            ])
            ->assertRedirect(route('website.testimonials.index'))
            ->assertSessionHasNoErrors();

        $row = $row->fresh();
        $this->assertSame('Sinta Dewi', $row->name);
        $this->assertSame('Sudah dipakai buat kado farewell.', $row->quote_id);
        $this->assertNull($row->quote_en);
        $this->assertSame('Farewell', $row->occasion_id);
        $this->assertNull($row->occasion_en);
        $this->assertSame(5, $row->sort_order);
        $this->assertFalse($row->is_active);
        $this->assertNotSame($oldPhoto, $row->photo);
        $this->assertStringStartsWith('website/testimonials/', $row->photo);
        Storage::disk('public')->assertMissing($oldPhoto);
        Storage::disk('public')->assertExists($row->photo);
    }

    public function test_testimonial_requires_name_and_quote_id(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('website.testimonials.store'), ['name' => '', 'quote_id' => ''])
            ->assertSessionHasErrors(['name', 'quote_id']);

        // quote_en alone is not enough
        $this->actingAs($admin)
            ->post(route('website.testimonials.store'), ['name' => 'Budi', 'quote_en' => 'English only'])
            ->assertSessionHasErrors('quote_id')
            ->assertSessionDoesntHaveErrors('name');

        // the same on update
        $row = $this->testimonial();
        $this->actingAs($admin)
            ->put(route('website.testimonials.update', $row), ['name' => '', 'quote_id' => ''])
            ->assertSessionHasErrors(['name', 'quote_id']);

        $this->assertSame(1, WebTestimonial::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_testimonial_length_limits_and_sort_order_bounds(): void
    {
        $admin = $this->admin();
        $valid = ['name' => 'Budi', 'quote_id' => 'Bagus'];

        $cases = [
            ['name' => str_repeat('a', 101)],
            ['quote_id' => str_repeat('a', 1001)],
            ['quote_en' => str_repeat('a', 1001)],
            ['occasion_id' => str_repeat('a', 101)],
            ['occasion_en' => str_repeat('a', 101)],
            ['sort_order' => -1],
            ['sort_order' => 'abc'],
            ['sort_order' => 65536],
        ];
        foreach ($cases as $override) {
            $this->actingAs($admin)
                ->post(route('website.testimonials.store'), $override + $valid)
                ->assertSessionHasErrors(array_key_first($override));
        }
        $this->assertSame(0, WebTestimonial::count());

        // exactly at the limits is fine
        $this->actingAs($admin)
            ->post(route('website.testimonials.store'), [
                'name' => str_repeat('a', 100),
                'quote_id' => str_repeat('b', 1000),
                'quote_en' => str_repeat('c', 1000),
                'occasion_id' => str_repeat('d', 100),
                'occasion_en' => str_repeat('e', 100),
                'sort_order' => 65535,
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, WebTestimonial::count());
    }

    public function test_testimonial_defaults_without_optional_fields(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('website.testimonials.store'), ['name' => 'Budi', 'quote_id' => 'Bagus', 'sort_order' => ''])
            ->assertRedirect(route('website.testimonials.index'))
            ->assertSessionHasNoErrors();

        $row = WebTestimonial::firstOrFail();
        $this->assertNull($row->quote_en);
        $this->assertNull($row->occasion_id);
        $this->assertNull($row->occasion_en);
        $this->assertNull($row->photo);
        $this->assertSame(0, $row->sort_order);
        $this->assertTrue($row->is_active);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_testimonial_photo_must_be_a_small_jpg_png_or_webp(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $valid = ['name' => 'Budi', 'quote_id' => 'Bagus'];

        foreach ([
            UploadedFile::fake()->image('big.jpg')->size(3000),
            UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->image('anim.gif'),
        ] as $bad) {
            $this->actingAs($admin)
                ->post(route('website.testimonials.store'), $valid + ['photo' => $bad])
                ->assertSessionHasErrors('photo');
        }

        $this->assertSame(0, WebTestimonial::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_update_without_new_photo_keeps_the_existing_one(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('keep.jpg')->store('website/testimonials', 'public');
        $row = $this->testimonial(['photo' => $path]);

        $this->actingAs($this->admin())
            ->put(route('website.testimonials.update', $row), ['name' => 'Renamed', 'quote_id' => 'Baru'])
            ->assertSessionHasNoErrors();

        $this->assertSame($path, $row->fresh()->photo);
        $this->assertSame('Renamed', $row->fresh()->name);
        Storage::disk('public')->assertExists($path);
    }

    public function test_delete_testimonial_removes_row_and_photo(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('gone.jpg')->store('website/testimonials', 'public');
        $row = $this->testimonial(['photo' => $path]);

        $this->actingAs($this->admin())
            ->delete(route('website.testimonials.destroy', $row))
            ->assertRedirect(route('website.testimonials.index'));

        $this->assertDatabaseMissing('web_testimonials', ['id' => $row->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_testimonial_index_lists_in_sort_order_and_forms_render(): void
    {
        $admin = $this->admin();
        $second = $this->testimonial(['name' => 'Kedua', 'sort_order' => 2]);
        $this->testimonial(['name' => 'Pertama', 'sort_order' => 1, 'quote_en' => 'First quote']);

        $this->actingAs($admin)
            ->get(route('website.testimonials.index'))
            ->assertOk()
            ->assertSeeInOrder(['Pertama', 'Kedua']);

        $this->actingAs($admin)
            ->get(route('website.testimonials.create'))
            ->assertOk()
            ->assertSee('quote_id', false)
            ->assertSee('name="photo"', false)
            ->assertSee('(EN, opsional)');

        $this->actingAs($admin)
            ->get(route('website.testimonials.edit', $second))
            ->assertOk()
            ->assertSee('Kedua');
    }

    public function test_testimonial_text_is_escaped_in_the_admin_views(): void
    {
        $admin = $this->admin();
        $row = $this->testimonial(['name' => '<script>alert(1)</script>', 'quote_id' => '<img src=x onerror=alert(2)>']);

        foreach ([route('website.testimonials.index'), route('website.testimonials.edit', $row)] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertDontSee('<script>alert(1)</script>', false)
                ->assertDontSee('<img src=x onerror=alert(2)>', false)
                ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        }
    }

    public function test_testimonial_cards_carry_the_delete_confirm_wording_and_thumbnails_are_lazy(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('t.jpg')->store('website/testimonials', 'public');
        $this->testimonial(['name' => 'Rina "R"', 'photo' => $path]);

        $this->actingAs($this->admin())
            ->get(route('website.testimonials.index'))
            ->assertOk()
            ->assertSee('data-confirm-title="Hapus testimoni?"', false)
            ->assertSee('data-confirm-text="Testimoni dari &quot;Rina &quot;R&quot;&quot; akan dihapus."', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('storage/' . $path, false);
    }

    public function test_categories_index_still_renders_its_cards_through_the_shared_partial(): void
    {
        $category = WebCategory::create(['slug' => 'ulang-tahun', 'name_id' => 'Ulang Tahun', 'name_en' => 'Birthday']);
        $category->products()->attach($this->webProduct()->id);

        $this->actingAs($this->admin())
            ->get(route('website.categories.index'))
            ->assertOk()
            ->assertSee('href="' . route('website.categories.edit', $category) . '"', false)
            ->assertSee('action="' . route('website.categories.destroy', $category) . '"', false)
            ->assertSee('data-name="Ulang Tahun"', false)
            ->assertSee('data-products="1"', false)
            ->assertSee('1 produk', false);
    }

    public function test_testimonial_flash_messages_are_in_indonesian(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('website.testimonials.store'), ['name' => 'Budi', 'quote_id' => 'Bagus'])
            ->assertSee('Testimoni berhasil ditambahkan');

        $row = WebTestimonial::firstOrFail();

        $this->actingAs($admin)
            ->followingRedirects()
            ->put(route('website.testimonials.update', $row), ['name' => 'Budi 2', 'quote_id' => 'Bagus'])
            ->assertSee('Testimoni berhasil diperbarui');

        $this->actingAs($admin)
            ->followingRedirects()
            ->delete(route('website.testimonials.destroy', $row))
            ->assertSee('Testimoni berhasil dihapus');
    }

    public function test_testimonial_validation_messages_are_in_indonesian(): void
    {
        $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('website.testimonials.create'))
            ->post(route('website.testimonials.store'), ['name' => '', 'quote_id' => ''])
            ->assertSee('Nama wajib diisi.')
            ->assertSee('Kutipan (ID) wajib diisi.');
    }

    // ---- FAQ ----

    public function test_admin_creates_faq_and_inactive_faq_is_hidden_from_site_api(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('website.faqs.store'), [
                'question_id' => 'Berapa lama pengerjaan?',
                'question_en' => 'How long does production take?',
                'answer_id' => 'Same-day atau H+1.',
                'answer_en' => 'Same-day or next day.',
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect(route('website.faqs.index'))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('website.faqs.store'), [
                'question_id' => 'Pertanyaan tersembunyi?',
                'answer_id' => 'Jawaban tersembunyi.',
                'sort_order' => 2,
                'is_active' => '0',
            ])
            ->assertRedirect(route('website.faqs.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, WebFaq::count());
        $visible = WebFaq::where('question_id', 'Berapa lama pengerjaan?')->firstOrFail();
        $hidden = WebFaq::where('question_id', 'Pertanyaan tersembunyi?')->firstOrFail();
        $this->assertSame('How long does production take?', $visible->question_en);
        $this->assertSame('Same-day or next day.', $visible->answer_en);
        $this->assertTrue($visible->is_active);
        $this->assertFalse($hidden->is_active);
        $this->assertNull($hidden->question_en);
        $this->assertNull($hidden->answer_en);

        $faqs = $this->getJson('/api/website/v1/site')->assertOk()->json('data.faqs');
        $this->assertSame([['question' => 'Berapa lama pengerjaan?', 'answer' => 'Same-day atau H+1.']], $faqs);

        // switching it on shows it; switching the other off hides that one
        $this->actingAs($admin)
            ->put(route('website.faqs.update', $hidden), [
                'question_id' => $hidden->question_id,
                'answer_id' => $hidden->answer_id,
                'sort_order' => 2,
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();
        $this->actingAs($admin)
            ->put(route('website.faqs.update', $visible), [
                'question_id' => $visible->question_id,
                'answer_id' => $visible->answer_id,
                'is_active' => '0',
            ])
            ->assertSessionHasNoErrors();

        $questions = array_column($this->getJson('/api/website/v1/site')->assertOk()->json('data.faqs'), 'question');
        $this->assertSame(['Pertanyaan tersembunyi?'], $questions);
    }

    public function test_faq_requires_question_id_and_answer_id(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('website.faqs.store'), ['question_id' => '', 'answer_id' => ''])
            ->assertSessionHasErrors(['question_id', 'answer_id']);

        // EN alone is not enough
        $this->actingAs($admin)
            ->post(route('website.faqs.store'), ['question_en' => 'Q?', 'answer_en' => 'A.'])
            ->assertSessionHasErrors(['question_id', 'answer_id']);

        $faq = $this->faq();
        $this->actingAs($admin)
            ->put(route('website.faqs.update', $faq), ['question_id' => '', 'answer_id' => ''])
            ->assertSessionHasErrors(['question_id', 'answer_id']);

        $this->assertSame(1, WebFaq::count());
    }

    public function test_faq_length_limits_and_sort_order_bounds(): void
    {
        $admin = $this->admin();
        $valid = ['question_id' => 'Q?', 'answer_id' => 'A.'];

        $cases = [
            ['question_id' => str_repeat('a', 256)],
            ['question_en' => str_repeat('a', 256)],
            ['answer_id' => str_repeat('a', 2001)],
            ['answer_en' => str_repeat('a', 2001)],
            ['sort_order' => -1],
            ['sort_order' => 65536],
        ];
        foreach ($cases as $override) {
            $this->actingAs($admin)
                ->post(route('website.faqs.store'), $override + $valid)
                ->assertSessionHasErrors(array_key_first($override));
        }
        $this->assertSame(0, WebFaq::count());

        $this->actingAs($admin)
            ->post(route('website.faqs.store'), [
                'question_id' => str_repeat('a', 255),
                'question_en' => str_repeat('b', 255),
                'answer_id' => str_repeat('c', 2000),
                'answer_en' => str_repeat('d', 2000),
                'sort_order' => 65535,
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, WebFaq::count());
    }

    public function test_faq_defaults_without_optional_fields(): void
    {
        $this->actingAs($this->admin())
            ->post(route('website.faqs.store'), ['question_id' => 'Q?', 'answer_id' => 'A.', 'sort_order' => ''])
            ->assertRedirect(route('website.faqs.index'))
            ->assertSessionHasNoErrors();

        $row = WebFaq::firstOrFail();
        $this->assertSame(0, $row->sort_order);
        $this->assertTrue($row->is_active);
        $this->assertNull($row->question_en);
        $this->assertNull($row->answer_en);
    }

    public function test_delete_faq_removes_row(): void
    {
        $faq = $this->faq();

        $this->actingAs($this->admin())
            ->delete(route('website.faqs.destroy', $faq))
            ->assertRedirect(route('website.faqs.index'));

        $this->assertDatabaseMissing('web_faqs', ['id' => $faq->id]);
    }

    public function test_faq_index_lists_in_sort_order_and_forms_render(): void
    {
        $admin = $this->admin();
        $second = $this->faq(['question_id' => 'Kedua?', 'sort_order' => 2, 'answer_en' => 'Second answer']);
        $this->faq(['question_id' => 'Pertama?', 'sort_order' => 1]);

        $this->actingAs($admin)
            ->get(route('website.faqs.index'))
            ->assertOk()
            ->assertSeeInOrder(['Pertama?', 'Kedua?']);

        $this->actingAs($admin)
            ->get(route('website.faqs.create'))
            ->assertOk()
            ->assertSee('question_id', false)
            ->assertSee('answer_id', false)
            ->assertSee('(EN, opsional)');

        $this->actingAs($admin)
            ->get(route('website.faqs.edit', $second))
            ->assertOk()
            ->assertSee('Kedua?')
            ->assertSee('Second answer');
    }

    public function test_faq_text_is_escaped_in_the_admin_views(): void
    {
        $admin = $this->admin();
        $faq = $this->faq(['question_id' => '<script>alert(1)</script>', 'answer_id' => '<b onmouseover=alert(2)>x</b>']);

        foreach ([route('website.faqs.index'), route('website.faqs.edit', $faq)] as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertDontSee('<script>alert(1)</script>', false)
                ->assertDontSee('<b onmouseover=alert(2)>', false)
                ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        }
    }

    public function test_faq_flash_messages_are_in_indonesian(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('website.faqs.store'), ['question_id' => 'Q?', 'answer_id' => 'A.'])
            ->assertSee('FAQ berhasil ditambahkan');

        $faq = WebFaq::firstOrFail();

        $this->actingAs($admin)
            ->followingRedirects()
            ->put(route('website.faqs.update', $faq), ['question_id' => 'Q2?', 'answer_id' => 'A.'])
            ->assertSee('FAQ berhasil diperbarui');

        $this->actingAs($admin)
            ->followingRedirects()
            ->delete(route('website.faqs.destroy', $faq))
            ->assertSee('FAQ berhasil dihapus');
    }

    public function test_faq_validation_messages_are_in_indonesian(): void
    {
        $this->actingAs($this->admin())
            ->followingRedirects()
            ->from(route('website.faqs.create'))
            ->post(route('website.faqs.store'), ['question_id' => '', 'answer_id' => ''])
            ->assertSee('Pertanyaan (ID) wajib diisi.')
            ->assertSee('Jawaban (ID) wajib diisi.');
    }
}
