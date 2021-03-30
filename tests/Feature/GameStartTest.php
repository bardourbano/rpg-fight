<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GameStartTest extends TestCase
{
    use DatabaseMigrations;
    use RefreshDatabase;
    
    /** @test */
    public function withoutDatabase()
    {
        $this->artisan('game:start')->assertExitCode(0);
    }

    /** @tes */
    public function withEmptyDatabase()
    {
        $this->artisan('migrate')->run();

        $this->artisan('game:start')
            ->expectsConfirmation('Já existe uma base de dados, deseja sobrescrevê-la? [y - sim | n - não]:', 'sim')
            ->assertExitCode(0);
    }
}
