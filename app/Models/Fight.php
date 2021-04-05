<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Character $hero
 * @property Character $monster
 * @property User $user
 */
class Fight extends Model
{
    use HasFactory;

    protected $casts = [
        'win' => 'boolean',
        'log' => 'array'
    ];
    protected $fillable = ['log', 'win'];

    public function hero(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'hero_id');
    }

    public function monster(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'monster_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
