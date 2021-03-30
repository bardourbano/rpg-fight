<?php

namespace Database\Seeders;

use App\Models\Character;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CharacterSeed extends Seeder
{
    public function run()
    {
        $characters = json_decode(file_get_contents('database/characters.json'), true);
        
        $characters = array_map(
            fn($character) => Arr::add($character, 'created_at', now()->format('Y-m-d h:m:s')),
            $characters['characters']
        );

        DB::table('characters')->insert($characters);
    }
}
