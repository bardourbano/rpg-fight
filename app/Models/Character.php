<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    use HasFactory;

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['damage'];

    /**
     * The attributes that should be hidden for arrays and jsons.
     */
    protected $hidden = ['created_at', 'updated_at', 'damage_dice', 'damage_factor'];

    public function scopeHero(Builder $query): Builder
    {
        return $query->where('type', 'hero');
    }
    
    public function scopeMonster(Builder $query): Builder
    {
        return $query->where('type', 'monster');
    }

    public function getDamageAttribute(): string
    {
        return $this->damage_factor . $this->damage_dice;
    }
}
