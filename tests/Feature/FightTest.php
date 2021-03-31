<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Fight;
use App\Models\User;
use Database\Seeders\CharacterSeed;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FightTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;

    private string $nickname;
    private string $password;
    private User $user;

    public function __construct()
    {
        $this->seed(CharacterSeed::class);

        $this->nickname = 'TesteMaster';
        $this->password = 'testword';

        $this->userc = User::factory([
            'nickname' => $nickname,
            'password' => Hash::make($password)
        ])->create();
    }

    /** @test */
    public function storeFight()
    {
        $fight = Fight::factory(['user_id' => $this->user->id])->create();

        $this->assertDatabaseHas('fights', ['id' => $fight->id]);
    }

    /** @test */
    public function startFight()
    {
        $hero = Character::hero()->get()->first();
        
        $response = $this->post("/fights", [

        ])
    }

    /** @tes */
    public function updateFight()
    {
        $fight = Fight::factory(['user_id' => $this->user->id])->create();

        $response = $this->patch("fights/$fight->id", [
            'win' => true,
            'turn' => 0,
            ''

        ])
    }
}
