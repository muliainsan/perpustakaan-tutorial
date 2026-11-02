<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $bookId = $this->route('book')?->id ?? $this->route('book');

        return [
            'category_id'  => ['required', 'exists:categories,id'],
            'title'        => ['required', 'string', 'max:255'],
            'isbn'         => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('books', 'isbn')->ignore($bookId),
            ],
            'publisher'    => ['nullable', 'string', 'max:255'],
            'year'         => ['nullable', 'integer', 'min:1000', 'max:' . ((int) date('Y') + 1)],
            'stock'        => ['required', 'integer', 'min:0'],
            'author_ids'   => ['required', 'array', 'min:1'],
            'author_ids.*' => ['exists:authors,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required'  => 'Kategori buku wajib dipilih.',
            'category_id.exists'    => 'Kategori yang dipilih tidak valid.',
            'title.required'        => 'Judul buku wajib diisi.',
            'title.max'             => 'Judul buku maksimal :max karakter.',
            'isbn.unique'           => 'Nomor ISBN sudah terdaftar.',
            'year.integer'          => 'Tahun terbit harus berupa angka.',
            'year.min'              => 'Tahun terbit minimal tahun :min.',
            'year.max'              => 'Tahun terbit tidak boleh melebihi tahun :max.',
            'stock.required'        => 'Stok buku wajib diisi.',
            'stock.integer'         => 'Stok buku harus berupa bilangan bulat.',
            'stock.min'             => 'Stok buku tidak boleh bernilai negatif.',
            'author_ids.required'   => 'Pilih minimal satu penulis untuk buku ini.',
            'author_ids.min'        => 'Pilih minimal satu penulis untuk buku ini.',
            'author_ids.*.exists'   => 'Salah satu penulis yang dipilih tidak valid.',
        ];
    }
}

