<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IniciarJogoTest extends TestCase
{
    /** @test */
    public function semBaseDeDados()
    {
        $this->artisan('jogo:iniciar')->assertExitCode(0);
    }

    /** @tes */
    public function comBaseDeDadosVazia()
    {
        $this->artisan('migrate')->run();

        $this->artisan('jogo:iniciar')
            ->expectsConfirmation('Já existe uma base de dados, deseja sobrescrevê-la? [y - sim | n - não]:', 'sim')
            ->assertExitCode(0);
    }
}
