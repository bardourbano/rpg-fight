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
        parent::__construct();

        $this->nickname = 'TesteMaster';
        $this->password = 'testword';
    }

    /** @test */
    public function storeFight()
    {
        $this->databaseSetUp();

        $fight = Fight::factory(['user_id' => $this->user->id])->create();

        $this->assertDatabaseHas('fights', ['id' => $fight->id]);
    }

    /** @test */
    public function startFight()
    {
        $this->databaseSetUp();

        $headers = [
            'nickname' => $this->nickname,
            'password' => $this->password
        ];

        $hero = Character::hero()->get()->first();
        $response = $this->post("/api/fights", ['hero_id' => $hero->id], $headers);

        $response->assertCreated();
        $response->assertJsonFragment($hero->toArray());
        $this->assertArrayHasKey('fight', $response->decodeResponseJson());
    }

    /** @test */
    public function doATurn()
    {
        $this->databaseSetUp();

        $fight = Fight::factory(['user_id' => $this->user->id])->create();
        
        $headers = [
            'nickname' => $this->nickname,
            'password' => $this->password
        ];

        $response = $this->patch("/api/fights/$fight->id", headers: $headers);

        $response->assertSuccessful();
    }

    private function databaseSetUp()
    {
        $this->seed(CharacterSeed::class);

        $this->user = User::factory([
            'nickname' => $this->nickname,
            'password' => Hash::make($this->password)
        ])->create();
    }
}
