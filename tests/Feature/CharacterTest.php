<?php

namespace Tests\Feature;

use App\Models\Character;
use Database\Seeders\CharacterSeed;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CharacterTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;
    
    /** @test */
    public function charactersFirstInsert()
    {
        $this->seed(CharacterSeed::class);

        $characters = Character::get([
            'agility',
            'class',
            'damage_dice',
            'damage_factor',
            'defense',
            'life_points',
            'strength',
            'type'
        ]);
        $json = json_decode(file_get_contents('database/characters.json'), true);

        $this->assertEquals($json['characters'], $characters->toArray());
    }
}
