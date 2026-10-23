<?php

namespace Tests\Feature\Website;

use App\Models\WebCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CategoryAdminTest extends WebsiteTestCase
{
    private function category(array $attrs = []): WebCategory
    {
        static $n = 0;
        $n++;

        return WebCategory::create($attrs + [
            'slug' => "kategori-{$n}",
            'name_id' => "Kategori {$n}",
        ]);
    }

    public function test_staff_and_guests_get_403(): void
    {
        $url = route('website.categories.index');

        // guest first: no actingAs yet
        $this->get($url)->assertForbidden();
        $this->post(route('website.categories.store'), ['name_id' => 'X'])->assertForbidden();

        $staff = $this->staff();
        $this->actingAs($staff)->get($url)->assertForbidden();
        $this->actingAs($staff)->get(route('website.categories.create'))->assertForbidden();
        $this->actingAs($staff)->post(route('website.categories.store'), ['name_id' => 'X'])->assertForbidden();

        $this->assertSame(0, WebCategory::count());
    }

    public function test_admin_creates_category_with_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('website.categories.store'), [
                'name_id' => 'Ulang Tahun',
                'name_en' => 'Birthday',
                'slug' => 'birthday',
                'sort_order' => 3,
                'is_active' => '1',
                'image' => UploadedFile::fake()->image('b.jpg'),
            ])
            ->assertRedirect(route('website.categories.index'))
            ->assertSessionHasNoErrors();

        $row = WebCategory::where('slug', 'birthday')->firstOrFail();
        $this->assertSame('Ulang Tahun', $row->name_id);
        $this->assertSame('Birthday', $row->name_en);
        $this->assertSame(3, $row->sort_order);
        $this->assertTrue($row->is_active);
        $this->assertStringStartsWith('website/categories/', $row->image);
        Storage::disk('public')->assertExists($row->image);
    }

    public function test_flash_messages_are_in_indonesian(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('website.categories.store'), ['name_id' => 'Wisuda'])
            ->assertSee('Kategori berhasil ditambahkan');

        $category = WebCategory::firstOrFail();

        $this->actingAs($admin)
            ->followingRedirects()
            ->put(route('website.categories.update', $category), ['name_id' => 'Wisuda 2', 'slug' => $category->slug])
            ->assertSee('Kategori berhasil diperbarui');

        $this->actingAs($admin)
            ->followingRedirects()
            ->delete(route('website.categories.destroy', $category))
            ->assertSee('Kategori berhasil dihapus');
    }

    public function test_create_without_optional_fields_uses_defaults(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('website.categories.store'), ['name_id' => 'Wisuda', 'sort_order' => ''])
            ->assertRedirect(route('website.categories.index'))
            ->assertSessionHasNoErrors();

        $row = WebCategory::firstOrFail();
        $this->assertNull($row->image);
        $this->assertNull($row->name_en);
        $this->assertSame(0, $row->sort_order);
        $this->assertTrue($row->is_active);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_empty_slug_falls_back_to_slugified_indonesian_name(): void
    {
        $this->actingAs($this->admin())
            ->post(route('website.categories.store'), ['name_id' => 'Hadiah Ulang Tahun!', 'slug' => ''])
            ->assertSessionHasNoErrors();

        $this->assertSame('hadiah-ulang-tahun', WebCategory::firstOrFail()->slug);
    }

    public function test_name_is_required_and_unsluggable_name_needs_a_manual_slug(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('website.categories.store'), ['name_id' => ''])
            ->assertSessionHasErrors('name_id')
            ->assertSessionDoesntHaveErrors('slug');

        $this->actingAs($admin)
            ->post(route('website.categories.store'), ['name_id' => '###'])
            ->assertSessionHasErrors('slug');

        $this->assertSame(0, WebCategory::count());
    }

    public function test_slug_must_be_unique_and_lowercase(): void
    {
        $admin = $this->admin();
        $this->category(['slug' => 'birthday']);

        foreach (['Birthday', 'bad slug', 'bad_slug', '-lead', 'trail-', 'dou--ble'] as $bad) {
            $this->actingAs($admin)
                ->post(route('website.categories.store'), ['name_id' => 'Baru', 'slug' => $bad])
                ->assertSessionHasErrors('slug');
        }

        // exact duplicate, and a duplicate produced by the empty-slug fallback
        $this->actingAs($admin)
            ->post(route('website.categories.store'), ['name_id' => 'Baru', 'slug' => 'birthday'])
            ->assertSessionHasErrors('slug');
        $this->actingAs($admin)
            ->post(route('website.categories.store'), ['name_id' => 'Birthday'])
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, WebCategory::count());

        $this->actingAs($admin)
            ->post(route('website.categories.store'), ['name_id' => 'Baru', 'slug' => 'new-one-2'])
            ->assertSessionHasNoErrors();
    }

    public function test_update_may_keep_its_own_slug_but_not_take_another(): void
    {
        $admin = $this->admin();
        $mine = $this->category(['slug' => 'mine']);
        $this->category(['slug' => 'other']);

        $this->actingAs($admin)
            ->put(route('website.categories.update', $mine), ['name_id' => 'Mine 2', 'slug' => 'mine'])
            ->assertRedirect(route('website.categories.index'))
            ->assertSessionHasNoErrors();
        $this->assertSame('Mine 2', $mine->fresh()->name_id);

        $this->actingAs($admin)
            ->put(route('website.categories.update', $mine), ['name_id' => 'Mine 2', 'slug' => 'other'])
            ->assertSessionHasErrors('slug');
        $this->assertSame('mine', $mine->fresh()->slug);
    }

    public function test_update_can_deactivate_a_category(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin())
            ->put(route('website.categories.update', $category), [
                'name_id' => $category->name_id,
                'slug' => $category->slug,
                'is_active' => '0',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_replacing_image_deletes_old_file(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $oldPath = UploadedFile::fake()->image('old.jpg')->store('website/categories', 'public');
        $category = $this->category(['image' => $oldPath]);
        Storage::disk('public')->assertExists($oldPath);

        $this->actingAs($admin)
            ->put(route('website.categories.update', $category), [
                'name_id' => $category->name_id,
                'slug' => $category->slug,
                'image' => UploadedFile::fake()->image('new.png'),
            ])
            ->assertRedirect(route('website.categories.index'))
            ->assertSessionHasNoErrors();

        $newPath = $category->fresh()->image;
        $this->assertNotSame($oldPath, $newPath);
        $this->assertStringStartsWith('website/categories/', $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_update_without_new_image_keeps_the_existing_one(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('keep.jpg')->store('website/categories', 'public');
        $category = $this->category(['image' => $path]);

        $this->actingAs($this->admin())
            ->put(route('website.categories.update', $category), [
                'name_id' => 'Renamed',
                'slug' => $category->slug,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($path, $category->fresh()->image);
        Storage::disk('public')->assertExists($path);
    }

    public function test_oversized_image_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('website.categories.store'), [
                'name_id' => 'Besar',
                'image' => UploadedFile::fake()->image('big.jpg')->size(3000),
            ])
            ->assertSessionHasErrors('image');

        $this->assertSame(0, WebCategory::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_non_image_and_disallowed_formats_are_rejected(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('website.categories.store'), [
                'name_id' => 'Dokumen',
                'image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');

        $this->actingAs($admin)
            ->post(route('website.categories.store'), [
                'name_id' => 'Gif',
                'image' => UploadedFile::fake()->image('anim.gif'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertSame(0, WebCategory::count());
    }

    public function test_delete_removes_row_and_image(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('gone.jpg')->store('website/categories', 'public');
        $category = $this->category(['image' => $path]);

        $this->actingAs($this->admin())
            ->delete(route('website.categories.destroy', $category))
            ->assertRedirect(route('website.categories.index'));

        $this->assertDatabaseMissing('web_categories', ['id' => $category->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_detaches_products_but_keeps_them(): void
    {
        $category = $this->category();
        $product = $this->webProduct();
        $category->products()->attach($product->id);

        $this->actingAs($this->admin())
            ->delete(route('website.categories.destroy', $category))
            ->assertRedirect(route('website.categories.index'));

        $this->assertDatabaseMissing('web_categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('web_category_product', ['web_category_id' => $category->id]);
        $this->assertDatabaseHas('web_products', ['id' => $product->id]);
    }

    public function test_index_lists_categories_in_sort_order_with_product_counts(): void
    {
        $second = $this->category(['name_id' => 'Kedua', 'sort_order' => 2]);
        $this->category(['name_id' => 'Pertama', 'sort_order' => 1]);
        $second->products()->attach($this->webProduct()->id);

        $this->actingAs($this->admin())
            ->get(route('website.categories.index'))
            ->assertOk()
            ->assertSeeInOrder(['Pertama', 'Kedua']);
    }

    public function test_create_and_edit_forms_render(): void
    {
        $category = $this->category(['name_id' => 'Sebelum', 'name_en' => 'Before']);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('website.categories.create'))
            ->assertOk()
            ->assertSee('name_id', false)
            ->assertSee('(EN, opsional)');

        $this->actingAs($admin)
            ->get(route('website.categories.edit', $category))
            ->assertOk()
            ->assertSee('Sebelum')
            ->assertSee('Before');
    }

    public function test_sidebar_shows_website_group_for_admin(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard/website/categories')
            ->assertOk()
            ->assertSee('Produk Website')
            ->assertSee('websiteSubmenu', false);
    }
}
