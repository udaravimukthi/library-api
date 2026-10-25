<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    /** @use HasFactory<\Database\Factories\BookFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'total_copies',
        'available_copies',
    ];

    protected function casts(): array
    {
        return [
            'total_copies' => 'integer',
            'available_copies' => 'integer',
        ];
    }

    // One book can have many loans
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function isAvailable(): bool
    {
        return $this->available_copies > 0;
    }

    // Used for ?author=martin in the books list
    public function scopeByAuthor(Builder $query, ?string $author): Builder
    {
        return $query->when($author, fn (Builder $q) => $q->where('author', 'like', "%{$author}%"));
    }
}
