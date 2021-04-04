<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\User;
use Database\Seeders\CharacterSeed;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
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

        $this->assertEquals(
            $json['characters'],
            $characters->makeVisible(['damage_dice', 'damage_factor'])->makeHidden('damage')->toArray()
        );
    }

    /** @test */
    public function heroesList()
    {
        $this->seed(CharacterSeed::class);
        
        $nickname = 'TestMaster';
        $password = 'testword';

        User::factory([
            'nickname' => $nickname,
            'password' => Hash::make($password)
        ])->create();

        $response = $this->get('/api/heroes', [
            'nickname' => $nickname,
            'password' => $password
        ]);

        $response->assertSuccessful();

        $heroes = Character::where('type', 'hero')->get()->makeHidden('type')->toArray();

        $response->assertJson($heroes);
    }
}
