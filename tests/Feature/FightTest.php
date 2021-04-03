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
    public function userFightsHistory()
    {
        $this->databaseSetUp();

        $count = 5;

        Fight::factory(['user_id' => $this->user->id, 'win' => true])->count($count)->create();
        
        $headers = [
            'nickname' => $this->nickname,
            'password' => $this->password
        ];

        $response = $this->get("/api/users/{$this->nickname}/fights", $headers);

        $response->assertOk();
        $response->assertJsonCount($count);
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
    public function userShouldNotbeAbleToStartASeccondFightWithoutFinishingThefirstOne()
    {
        $this->databaseSetUp();

        $fight = Fight::factory(['user_id' => $this->user->id])->create();
        $headers = [
            'nickname' => $this->nickname,
            'password' => $this->password
        ];
        $hero = Character::hero()->get()->first();

        $response = $this->post('/api/fights', ["hero_id" => $hero->id], $headers);

        $response->assertStatus(409);
    }

    /** @test */
    public function firstTurn()
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

    /** @test */
    public function subsequentTurns()
    {
        $this->databaseSetUp();

        $log = [
            "turn_1" => [
                "initiative" => [
                    "hero_initiative" => 4,
                    "hero_roll" => 1,
                    "monster_initiative" => 5,
                    "monster_roll" => 4
                ],
                "attack_1" => [
                    "attacker" => "monster",
                    "attacker_roll" => 4,
                    "attack_result" => 9,
                    "defender" => "hero",
                    "defender_roll" => 9,
                    "defense_result" => 13,
                ],
                "attack_2" => [
                    "attacker" => "hero",
                    "attacker_roll" => 5,
                    "attack_result" => 14,
                    "defender" => "monster",
                    "defender_roll" => 4,
                    "defense_result" => 5,
                    "damage" => 18,
                    "defender_life_points" => 7
                ],
                "hero_current_life_points" => 13,
                "monster_current_life_points" => 7
            ]
        ];
        $hero = Character::where('class', 'Barbarian')->first();
        $monster = Character::where('class', 'Undead')->first();

        $fight = Fight::factory([
            'user_id' => $this->user->id,
            'hero_id' => $hero->id,
            'monster_id' => $monster->id,
            'log' => $log
        ])->create();

        $headers = [
            'nickname' => $this->nickname,
            'password' => $this->password
        ];

        $response = $this->patch("/api/fights/$fight->id", headers: $headers);

        $response->assertSuccessful();
    }

    /** @test */
    public function userWinsTheFightWhenMonsterLifePointsReachZero()
    {
        $this->databaseSetUp();
        
        $log = [
            "turn_1" => [
                "hero_current_life_points" => 130,
                "monster_current_life_points" => 1
            ]
        ];
        $hero = Character::where('class', 'Barbarian')->first();
        $monster = Character::where('class', 'Undead')->first();

        $fight = Fight::factory([
            'user_id' => $this->user->id,
            'hero_id' => $hero->id,
            'monster_id' => $monster->id,
            'log' => $log
        ])->create();

        $headers = [
            'nickname' => $this->nickname,
            'password' => $this->password
        ];

        $response = $this->patch("/api/fights/$fight->id", headers: $headers);
        
        $response->assertSuccessful();
        $response->assertJsonFragment(["result" => "winner"]);
    }

    private function databaseSetUp()
    {
        $this->seed(CharacterSeed::class);

        $this->user = User::factory([
            'nickname' => $this->nickname,
            'password' => Hash::make($this->password),
            'score' => 0
        ])->create();
    }
}
