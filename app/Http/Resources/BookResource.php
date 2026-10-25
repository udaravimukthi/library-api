<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'total_copies' => $this->total_copies,
            'available_copies' => $this->available_copies,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
