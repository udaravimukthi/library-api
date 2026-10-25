<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
    ];

    // One member can have many loans
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoansCount(): int
    {
        return $this->loans()->active()->count();
    }

    public function hasOverdueLoans(): bool
    {
        return $this->loans()->overdue()->exists();
    }
}
