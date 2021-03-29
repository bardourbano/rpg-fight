<?php

namespace Database\Seeders;

use App\Models\Personagem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PersonagemSeed extends Seeder
{
    public function run()
    {
        $personagens = json_decode(file_get_contents('database/personagens.json'), true);
        
        $personagens = array_map(
            fn($personagem) => Arr::add($personagem, 'created_at', now()->format('Y-m-d h:m:s')),
            $personagens['personagens']
        );

        DB::table('personagens')->insert($personagens);
    }
}
