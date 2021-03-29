<?php

namespace Tests\Feature;

use App\Models\Personagem;
use Database\Seeders\PersonagemSeed;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PersonagemTest extends TestCase
{
    use DatabaseMigrations;
    
    /** @test */
    public function seedDeveriaInserirTodosPersonagens()
    {
        $this->seed(PersonagemSeed::class);

        $personagens = Personagem::get([
            'agilidade',
            'classe',
            'dado_dano',
            'defesa',
            'fator_dano',
            'forca',
            'pontos_vida',
            'tipo'
        ]);
        $json = json_decode(file_get_contents('database/personagens.json'), true);

        $this->assertEquals($json['personagens'], $personagens->toArray());
    }
}
