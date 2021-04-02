<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CharacterSeed;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GameSetupTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function withoutDatabase()
    {
        $this->artisan('game:setup --no-server')->assertExitCode(0);
        $this->assertDatabaseHas('characters', ['id' => 6]);
    }

    /** @test */
    public function withEmptyDatabase()
    {
        $this->artisan('migrate')->run();

        $this->artisan('game:setup --no-server')
            ->expectsOutput("Preparing database...")
            ->assertExitCode(0);

        $this->assertDatabaseHas('characters', ['id' => 6]);
    }

    /** @test */
    public function withFilledDatabase()
    {
        $this->artisan('migrate');
        $this->seed(CharacterSeed::class);

        User::factory()->create();

        $this->artisan('game:setup --no-server')
            ->expectsOutput("Preparing database...")
            ->expectsConfirmation(
                'Database for the game alredy exists. Do you wish to overwrite it?:', 'yes'
            )
            ->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => 1]);
    }
}
