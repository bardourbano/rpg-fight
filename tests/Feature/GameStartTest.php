<?php

namespace Tests\Feature;

use App\Models\Character;
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
            ->expectsOutput("The nickname $this->nickname alredy exists.")
            ->expectsConfirmation("Would you like to try a different one?", 'no')
            ->assertExitCode(1);
    }

    /** @test */
    public function playerAlredyRegistered()
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
            ->expectsOutput("Your opponent is a {$monster->class}!");
    }
}
