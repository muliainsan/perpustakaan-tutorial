<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    public function test_can_view_category_index_page(): void
    {
        $response = $this->get(route('admin.categories.index'));

        $response->assertStatus(200);
        $response->assertSee('Kelola Kategori Buku');
    }

    public function test_can_view_create_category_form(): void
    {
        $response = $this->get(route('admin.categories.create'));

        $response->assertStatus(200);
        $response->assertSee('Tambah Kategori Buku Baru');
    }

    public function test_can_store_new_category(): void
    {
        $uniqueName = 'Kategori Uji ' . uniqid();

        $response = $this->post(route('admin.categories.store'), [
            'name' => $uniqueName,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success', 'Kategori berhasil ditambahkan.');

        $this->assertDatabaseHas('categories', [
            'name' => $uniqueName,
        ]);
    }

    public function test_store_validation_rejects_duplicate_category_name(): void
    {
        $existing = Category::first();
        $this->assertNotNull($existing);

        $response = $this->post(route('admin.categories.store'), [
            'name' => $existing->name,
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_can_update_category(): void
    {
        $category = Category::create(['name' => 'Kategori Lama ' . uniqid()]);
        $updatedName = 'Kategori Baru ' . uniqid();

        $response = $this->put(route('admin.categories.update', $category), [
            'name' => $updatedName,
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success', 'Kategori berhasil diperbarui.');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => $updatedName,
        ]);
    }

    public function test_can_delete_category_without_books(): void
    {
        $category = Category::create(['name' => 'Kategori Kosong ' . uniqid()]);

        $response = $this->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('success', 'Kategori berhasil dihapus.');

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_restrict_prevents_deleting_category_with_books_and_flashes_friendly_error(): void
    {
        // Cari kategori yang memiliki buku dari seeder
        $category = Category::has('books')->first();
        $this->assertNotNull($category);

        $response = $this->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('error', 'Kategori tidak dapat dihapus karena masih memuat buku.');

        // Pastikan kategori tetap ada di basis data
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }
}
