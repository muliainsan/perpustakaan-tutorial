<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'user'        => $this->whenLoaded('user', function () {
                return [
                    'id'          => $this->user->id,
                    'name'        => $this->user->name,
                    'email'       => $this->user->email,
                    'member_code' => $this->user->member_code,
                ];
            }),
            'loaned_at'   => $this->loaned_at?->format('Y-m-d'),
            'due_date'    => $this->due_date?->format('Y-m-d'),
            'returned_at' => $this->returned_at?->format('Y-m-d'),
            'fine_amount' => (float) $this->fine_amount,
            'fine_status' => $this->fine_status,
            'is_overdue'  => $this->returned_at === null && $this->due_date && $this->due_date->isPast(),
            'books'       => $this->whenLoaded('loanItems', function () {
                return $this->loanItems->map(function ($item) {
                    return [
                        'book_id' => $item->book_id,
                        'title'   => $item->book?->title,
                        'isbn'    => $item->book?->isbn,
                    ];
                });
            }),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
