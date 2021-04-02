<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class FightController extends Controller
{
    private array $dices = [
        'd2' => 2,
        'd4' => 4,
        'd6' => 6,
        'd8' => 8,
        'd10' => 10
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(User $user)
    {
        $fights = $user->fights;
        return response($fights->toJson());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        $user = User::where('nickname', $request->header('nickname'))->first();
        $fights = Fight::where('user_id', $user->id)->where('win', null)->get('id');

        if (filled($fights)) {
            return abort(409, 'Theres is alredy one battle in couse');
        }
        
        $hero = Character::find($request->hero_id);
        
        $monster = Character::monster()->get('id')->toArray();
        $monster = Character::where('id', Arr::random($monster))->first();

    
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
        $hero = $fight->hero;
        $monster = $fight->monster;
        $user = $fight->user;

        $log = $fight->log;
        $attack_phases = 0;

        if (filled($log)) {
            $hero_lp = end($log)['hero_current_life_points'];
            $monster_lp = end($log)['monster_current_life_points'];

            $turn_number = "turn_" . (count($log) + 1);
        } else {
            $hero_lp = $hero->life_points;
            $monster_lp = $monster->life_points;

            $turn_number = "turn_1";
        }

        /*
         * Initiative
         */
        do {
            $hero_roll = random_int(1, 10);
            $hero_initiative = $hero->agility + $hero_roll;

            $monster_roll = random_int(1, 10);
            $monster_initiative = $monster->agility + $monster_roll;
        } while ($hero_initiative == $monster_initiative);

        $initiative = ['initiative' => [
            'hero_initiative' => $hero_initiative,
            'hero_roll' => $hero_roll,
            'monster_initiative' => $monster_initiative,
            'monster_roll' => $monster_roll
            ]
        ];

        do {
            switch (true) {
                /*
                 * Hero attacks
                 */
                case ($hero_initiative > $monster_initiative && $attack_phases == 0):
                case ($hero_initiative < $monster_initiative && $attack_phases == 1):
                    list(
                        $attacks["attack_" . $attack_phases + 1],
                        $is_someone_dead,
                        $monster_lp
                    ) = $this->attackPhase(
                        attacker: $hero,
                        defender: $monster,
                        defender_lp: $monster_lp
                    );
                    break;
                
                /*
                 * Monster attacks
                 */
                case ($hero_initiative < $monster_initiative && $attack_phases == 0):
                case ($hero_initiative > $monster_initiative && $attack_phases == 1):
                    list(
                        $attacks["attack_" . $attack_phases + 1],
                        $is_someone_dead,
                        $hero_lp
                    ) = $this->attackPhase(
                        attacker: $monster,
                        defender: $hero,
                        defender_lp: $hero_lp
                    );
                    break;
            }

            $attack_phases++;
        } while (!$is_someone_dead && $attack_phases < 2);

        if (empty($log[$turn_number])) {
            $log[$turn_number] = $initiative;
        } else {
            $log[$turn_number] += $initiative;
        }

        $log[$turn_number] += $attacks;
        $log[$turn_number] += ['hero_current_life_points' => $hero_lp];
        $log[$turn_number] += ['monster_current_life_points' => $monster_lp];

        $content = [
            'hero' => $hero,
            'hero_current_life_points' => $hero_lp,
            'monster' => $monster,
            'monster_current_life_points' => $monster_lp,
            'fight' => [
                'id' => $fight->id,
                $turn_number => $log[$turn_number]
            ]
        ];
        
        $is_someone_dead = true;
        if ($is_someone_dead) {
            $monster_lp = -1;
            if ($monster_lp <= 0) {
                $fight->win = true;
                $fight->score = 100 - Str::after($turn_number, '_');
                
                $user->score += $fight->score;
                $user->save();
                $content['result'] = 'winner';
                $content['score'] = $fight->score;
            } else {
                $fight->win = false;
                $content['result'] = 'looser';
            }
        }

        $fight->log = $log;
        $fight->save();
           
        return response(json_encode($content));
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

    private function attackRoll(Character $attacker, Character $defender): array
    {
        $attacker_roll = random_int(1, 10);
        $attack_result = $attacker_roll + $attacker->agility + $attacker->strength;

        $defender_roll = random_int(1, 10);
        $defense_result = $defender_roll + $defender->agility + $defender->defense;
    
        return [
            'attacker' => $attacker->type,
            'attacker_roll' => $attacker_roll,
            'attack_result' => $attack_result,
            'defender' => $defender->type,
            'defender_roll' => $defender_roll,
            'defense_result' => $defense_result
        ];
    }

    private function calculateDamage(Character $attacker, Character $defender, int $defender_lp): array
    {
        $damage = $attacker->strength;

        for ($i = 0; $i < $attacker->damage_factor ; $i++) { 
            $damage += random_int(1, $this->dices[$attacker->damage_dice]);
        }

        $defender_lp = $defender_lp - $damage;

        return [$damage, $defender_lp];
    }

    public function attackPhase(Character $attacker, Character $defender, int $defender_lp): array
    {
        $attack = [];
        $is_someone_dead = false;

        $attack = $this->attackRoll($attacker, $defender);

        /*
         * Damage calculation
         */
        if ($attack['attack_result'] > $attack['defense_result']) {
            list($damage, $defender_lp) = $this->calculateDamage($attacker, $defender, $defender_lp);

            $attack['damage'] = $damage;
            $attack['defender_life_points'] = $defender_lp;

            $is_someone_dead = ($defender_lp <= 0);
        
        }

        return [$attack, $is_someone_dead, $defender_lp];
    }
}
