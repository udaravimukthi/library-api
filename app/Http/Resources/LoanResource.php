<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book' => new BookResource($this->whenLoaded('book')),  // only if loaded with with()/load()
            'member_id' => $this->member_id,
            'borrowed_at' => $this->borrowed_at->toIso8601String(),
            'due_at' => $this->due_at->toIso8601String(),
            'returned_at' => $this->returned_at?->toIso8601String(),
            'status' => match (true) {
                $this->isReturned() => 'returned',
                $this->isOverdue() => 'overdue',
                default => 'active',
            },
            'days_overdue' => $this->daysOverdue(),
            'fine' => $this->fine(),
        ];
    }
}
