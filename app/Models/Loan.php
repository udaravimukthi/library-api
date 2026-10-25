<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Loan extends Model
{
    /** @use HasFactory<\Database\Factories\LoanFactory> */
    use HasFactory;

    public const FINE_PER_DAY = 50; // LKR per started day late

    protected $fillable = [
        'book_id',
        'member_id',
        'borrowed_at',
        'due_at',
        'returned_at',
    ];

    // Turn these columns into Carbon date objects automatically
    protected function casts(): array
    {
        return [
            'borrowed_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    // Each loan belongs to one book and one member
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    // Scope: Loan::active() = not returned yet
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    // Scope: Loan::overdue() = not returned AND due date has passed
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->active()->where('due_at', '<', now());
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isReturned() && $this->due_at->isPast();
    }

    // Days late, counted to the return date (or to now if still borrowed)
    public function daysOverdue(): int
    {
        $end = $this->returned_at ?? Carbon::now();

        if ($end->lessThanOrEqualTo($this->due_at)) {
            return 0;
        }

        return (int) ceil($this->due_at->diffInDays($end));
    }

    public function fine(): int
    {
        return $this->daysOverdue() * self::FINE_PER_DAY;
    }
}
