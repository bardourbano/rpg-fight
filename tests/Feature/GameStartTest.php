<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Fight;
use App\Models\User;
use Database\Seeders\CharacterSeed;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GameStartTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;

    private string $command = 'game:start';
    private string $nickname = 'TestMaster';
    private string $password = 'testword';

    /** @test */
    public function registerPlayer()
    {
        Http::fake([
            "*/users" => Http::response(status: 201)
        ]);

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'no')
            ->expectsOutput("Let's register you!")
            ->expectsQuestion("How would you like to be called (your nickname)?", $this->nickname)
            ->expectsOutput("Ok. TestMaster will be your nickname.")
            ->expectsQuestion("Now choose a password", $this->password)
            ->expectsOutput("User registered!")
            ->expectsQuestion("What would you like to do?", "Exit")
            ->assertExitCode(0);
    }

    /** @test */
    public function refuseDuplicatedNickname()
    {
        Http::fake([
            "*/users" => Http::response(status: 409)
        ]);

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'no')
            ->expectsOutput("Let's register you!")
            ->expectsQuestion("How would you like to be called (your nickname)?", $this->nickname)
            ->expectsOutput("Ok. TestMaster will be your nickname.")
            ->expectsQuestion("Now choose a password", $this->password)
            ->expectsOutput("The nickname $this->nickname already exists.")
            ->expectsConfirmation("Would you like to try a different one?", 'no')
            ->assertExitCode(1);
    }

    /** @test */
    public function playeralreadyRegistered()
    {
        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsQuestion("What would you like to do?", "Exit")
            ->assertExitCode(0);
    }

    /** @test */
    public function menuPresentation()
    {
        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsChoice(
                question: "What would you like to do?",
                answer: "Exit",
                answers: [
                    "Start a new battle",
                    "Continue battle",
                    "See my infos",
                    "See my battle history",
                    "See ranking",
                    "Exit"
                ]
            )
            ->assertExitCode(0);
    }

    /** @test */
    public function shouldHandleMissingCredentials()
    {
        Http::fake([
            '*/api/*' => Http::response('Nickname and Password required in header to access this route.', 400)
        ]);

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsQuestion("What would you like to do?", "See ranking")
            ->expectsOutput("Nickname and/or password missing. Try again.")
            ->assertExitCode(1);
    }

    /** @test */
    public function shouldHandleRefusedCredentials()
    {
        Http::fake([
            '*/api/*' => Http::response('Nickname and/or Password incorrect.', 401)
        ]);

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsQuestion("What would you like to do?", "See ranking")
            ->expectsOutput("Nickname and/or Password incorrect.")
            ->assertExitCode(1);
    }

    /** @test */
    public function userInfos()
    {
        $user = User::factory(['nickname' => $this->nickname, 'password' => Hash::make($this->password)])->create();

        Http::fake([
            "*/users/{$user->nickname}" => Http::response($user->toJson(), 200)
        ]);

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsQuestion("What would you like to do?", "See my infos")
            ->expectsOutput("Here is the information about you:")
            ->expectsOutput("Nickname: $user->nickname")
            ->expectsOutput("Score: $user->score")
            ->expectsQuestion("What would you like to do?", "Exit")
            ->assertExitCode(0);
    }

    /** @test */
    public function ranking()
    {
        User::factory(['nickname' => $this->nickname, 'password' => Hash::make($this->password)])->create();
        User::factory()->count(3)->create();

        $ranking = User::orderByDesc('score')->get(['nickname', 'score']);

        Http::fake([
            "*/ranking" => Http::response($ranking->toJson())
        ]);

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsQuestion("What would you like to do?", "See ranking")
            ->expectsTable(
                ['Nickname', 'Score'],
                $ranking->toArray()
            )
            ->expectsQuestion("What would you like to do?", "Exit")
            ->assertExitCode(0);
    }

    /** @test */
    public function newBattle()
    {
        User::factory(['nickname' => $this->nickname, 'password' => Hash::make($this->password)])->create();
        $this->seed(CharacterSeed::class);

        $heroes = Character::hero()->get();
        $monster = Character::monster()->first();

        $content = [
            'hero' => $heroes->first()->toArray(),
            'monster' => $monster->toArray(),
            'fight' => ['id' => 1]
        ];

        Http::fake([
            '*/heroes' => Http::response($heroes->makeHidden('type')->toJson()),
            '*/fights' => Http::response(
                body: json_encode($content),
                status: 201
            )
        ]);

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsQuestion("What would you like to do?", "Start a new battle")
            ->expectsTable(
                ['ID', 'Class', 'Life Points', 'Strength', 'Defense', 'Agility', 'Damage'],
                $heroes->makeHidden('type')->toArray()
            )
            ->expectsQuestion("Please, inform the ID of the chosen class", $heroes->first()->id)
            ->expectsOutput("Your opponent is a {$monster->class}!")
            ->expectsOutput("It's time to fight!")
            ->expectsQuestion("What will you do?", "Exit")
            ->expectsQuestion("What would you like to do?", "Exit")
            ->assertExitCode(0);
    }

    /** @test */
    public function newBattleAcceptsOnlyHeroesIds()
    {
        User::factory(['nickname' => $this->nickname, 'password' => Hash::make($this->password)])->create();
        $this->seed(CharacterSeed::class);

        $heroes = Character::hero()->get();
        $monster = Character::monster()->first();
        

        $content = [
            'hero' => $heroes->first()->toArray(),
            'monster' => $monster->toArray(),
            'fight' => ['id' => 1]
        ];

        Http::fake([
            '*/heroes' => Http::response($heroes->makeHidden('type')->toJson()),
            '*/fights' => Http::response(
                body: json_encode($content),
                status: 201
            )
        ]);

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsQuestion("What would you like to do?", "Start a new battle")
            ->expectsTable(
                ['ID', 'Class', 'Life Points', 'Strength', 'Defense', 'Agility', 'Damage'],
                $heroes->makeHidden('type')->toArray()
            )
            ->expectsQuestion("Please, inform the ID of the chosen class", $monster->id)
            ->expectsOutput("ID {$monster->id} isn't an Hero ID. Please, try again and choose a valid ID.")
            ->assertExitCode(1);
    }

    /** @test */
    public function shouldNotStartANewBattleIfUseralreadyHasOneInCourse()
    {
        $this->seed(CharacterSeed::class);
        $user = User::factory(['nickname' => $this->nickname, 'password' => Hash::make($this->password)])->create();

        $monster = Character::monster()->first();
        $heroes = Character::hero()->get();
        
        Fight::factory([
            'monster_id' => $monster->id,
            'hero_id' => $heroes->first()->id,
            'user_id' => $user->id
        ]);

        Http::fake([
            '*/heroes' => Http::response($heroes->makeHidden('type')->toJson()),
            '*/fights' => Http::response('Theres is already one battle in couse', 409)
        ]);

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsQuestion("What would you like to do?", "Start a new battle")
            ->expectsTable(
                ['ID', 'Class', 'Life Points', 'Strength', 'Defense', 'Agility', 'Damage'],
                $heroes->makeHidden('type')->toArray()
            )
            ->expectsQuestion("Please, inform the ID of the chosen class", $heroes->first()->id)
            ->expectsOutput("There is already a battle in course. Finish it before starting a new one.");
    }

    /** @tes */
    public function fightTurn()
    {
        User::factory(['nickname' => $this->nickname, 'password' => Hash::make($this->password)])->create();
        $this->seed(CharacterSeed::class);

        $heroes = Character::hero()->get();
        $monster = Character::monster()->first();

        $content = [
            'hero' => $heroes->first()->toArray(),
            'monster' => $monster->toArray(),
            'fight' => ['id' => 1]
        ];

        $fightContent = [
            'hero' => $heroes->first()->toArray(),
            'hero_current_life_points' => 10,
            'monster' => $monster->toArray(),
            'monster_current_life_points' => 9,
            'fight' => [
                'id' => 1,
                'turn_1' => [
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
            ]
        ];

        $battle_log = $fightContent['fight']['turn_1'];

        Http::fake([
            '*/heroes' => Http::response($heroes->makeHidden('type')->toJson()),
            '*/fights' => Http::response(
                body: json_encode($content),
                status: 201,
            ),
            '*/fights/*' => Http::response(json_encode($fightContent))
        ]);

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsQuestion("What would you like to do?", "Start a new battle")
            ->expectsTable(
                ['ID', 'Class', 'Life Points', 'Strength', 'Defense', 'Agility', 'Damage'],
                $heroes->makeHidden('type')->toArray()
            )
            ->expectsQuestion("Please, inform the ID of the chosen class", $heroes->first()->id)
            ->expectsOutput("Your opponent is a {$monster->class}!")
            ->expectsOutput("It's time to fight!")
            ->expectsChoice(
                question: 'What will you do?',
                answer: 'Fight',
                answers: [
                    'Fight',
                    'Exit'
                ]
            )
            /** @todo descobrir como solucionar o erro no script de testes que aparece a partir daqui */
            ->expectsOutput("Time to roll initiative!")
            ->expectsOutput("Your roll is {$battle_log['initiative']['hero_roll']}")
            ->expectsOutput("Your initiative is {$battle_log['initiative']['hero_initiative']}!")
            ->expectsOutput(
                "Unfortunately,
                 the monster's bloodlust speaks louder than your determination and he attacks before you can react"
            )
            ->expectsOutput("Defend youserlf!")
            ->expectsOutput("The die result is {$battle_log['attack_2']['defense_roll']}")
            ->expectsOutput("Your defense roll is {$battle_log['attack_2']['defense_result']}")
            ->expectsOutput(
                "Your training proved its worth 
                 and you defend yourself with mastery of the creature's rampant attack"
            )
            ->expectsOutput(
                "Thanks to your training and combat experience,
                 you were ready to attack long before your opponent realized the danger"
            )
            ->expectsOutput("Attack, mighty hero!")
            ->expectsOutput("The die result is {$battle_log['attack_2']['attacker_roll']}")
            ->expectsOutput("Your attack roll is {$battle_log['attack_2']['attack_result']}")
            ->expectsOutput("Despite its best efforts, the creature was unable to escape its attack")
            ->expectsOutput("You have caused {$battle_log['attack_2']['damage']} points of damage.")
            ->expectsQuestion('What will you do?', 'Exit')
            ->expectsQuestion("What would you like to do?", "Exit");
    }

    /** @test */
    public function battleHistory()
    {
        $this->seed(CharacterSeed::class);
        $user = User::factory(['nickname' => $this->nickname, 'password' => Hash::make($this->password)])->create();

        $monster = Character::monster()->first();
        $heroes = Character::hero()->get();

        Fight::factory(['user_id' => $user->id, 'win' => true])->count(4)->create();

        $fights = $user->fights;
        $fights->loadMissing(['monster', 'hero']);

        Http::fake([
            "*/users/{$user->nickname}/fights" => Http::response($fights->toJson())
        ]);

        $rows = array_map(
            fn($battle) => [
                'id' => $battle['id'],
                'hero' => $battle['hero']['class'],
                'monster' => $battle['monster']['class'],
                'result' => match($battle['win']) {
                    true => 'Winner',
                    false => 'Looser',
                    null => null
                },
                'score' => $battle['score']
            ],
            $fights->toArray()
        );

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsQuestion("What would you like to do?", "See my battle history")
            ->expectsTable(
                ['ID', 'Hero', 'Monster', 'Result', 'Score'],
                $rows
            )
            ->expectsQuestion("What would you like to do?", "Exit");
    }

    /** @test */
    public function continueBattle()
    {
        $this->seed(CharacterSeed::class);
        $user = User::factory(['nickname' => $this->nickname, 'password' => Hash::make($this->password)])->create();

        $monster = Character::monster()->first();
        $heroes = Character::hero()->get();
     
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

        Fight::factory(['user_id' => $user->id, 'win' => true])->count(4)->create();
        Fight::factory([
            'monster_id' => $monster->id,
            'hero_id' => $heroes->first()->id,
            'user_id' => $user->id,
            'log' => $log
        ])->create();

        $fights = $user->fights;
        $fights->loadMissing(['monster', 'hero']);

        $fight = $fights->where('win', null)->first();
        
        Http::fake([
            "*/users/{$user->nickname}/fights" => Http::response($fights->toJson())
        ]);

        $this->artisan($this->command)
            ->expectsOutput("Hello Player!")
            ->expectsConfirmation('Do you have a nickname?', 'yes')
            ->expectsQuestion("What's your nickname?", $this->nickname)
            ->expectsQuestion("And what's your password?", $this->password)
            ->expectsQuestion("What would you like to do?", "Continue battle")
            ->expectsOutput("There is a battle of a {$fight->hero->class} against a {$fight->monster->class}")
            ->expectsOutput("Your current life points: " . end($log)['hero_current_life_points'])
            ->expectsQuestion("Wish to continue this battle?", 'yes')
            ->expectsQuestion("What will you do?", 'Exit')
            ->expectsQuestion("What would you like to do?", "Exit");
    }
}
