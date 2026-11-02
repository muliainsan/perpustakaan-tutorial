<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'isbn'         => $this->isbn,
            'publisher'    => $this->publisher,
            'year'         => $this->year,
            'stock'        => (int) $this->stock,
            'is_available' => $this->stock > 0,
            'category'     => new CategoryResource($this->whenLoaded('category')),
            'authors'      => AuthorResource::collection($this->whenLoaded('authors')),
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}
