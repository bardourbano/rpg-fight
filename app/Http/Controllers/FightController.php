<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Fight;
use App\Models\User;
use Composer\Util\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class FightController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        $hero = Character::find($request->hero_id);
        
        $monster = Character::monster()->get('id')->toArray();
        $monster = Character::where('id', Arr::random($monster))->first();

        $user = User::where('nickname', $request->header('nickname'))->first();
    
        $fight = new Fight;
        $fight->hero()->associate($hero);
        $fight->monster()->associate($monster);
        $fight->user()->associate($user);
        $fight->save();

        $content = [
            'hero' => $hero->toArray(),
            'monster' => $monster->toArray(),
            'fight' => ['id' => $fight->id]
        ];

        return response(json_encode($content), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Fight  $fight
     * @return \Illuminate\Http\Response
     */
    public function show(Fight $fight)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fight $fight): Response
    {
        do {
            $hero_roll = random_int(1, 10);
            $hero_initiative = $fight->hero->agility + $hero_roll;

            $monster_roll = random_int(1, 10);
            $monster_initiative = $fight->monster->agility + $monster_roll;
        } while ($hero_initiative == $monster_initiative);

        $initiative = ['initiative' => [
            'hero_initiative' => $hero_initiative,
            'hero_roll' => $hero_roll,
            'monster_initiative' => $monster_initiative,
            'monster_roll' => $monster_roll
            ]
        ];

        if ($hero_initiative > $monster_initiative) {
            $hero_roll = random_int(1, 10);
            $hero_attack = $hero_roll + $fight->hero->agility + $fight->hero->strength;

            $monster_roll = random_int(1, 10);
            $monster_defense = $monster_roll + $fight->monster->agility + $fight->monster->strength;
        
            if ($hero_attack > $monster_defense) {
                
            } else 
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Fight  $fight
     * @return \Illuminate\Http\Response
     */
    public function destroy(Fight $fight)
    {
        //
    }
}
