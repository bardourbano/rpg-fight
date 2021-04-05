<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GameStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes the game';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line("Hello Player!");
        $this->newLine();

        /*
         * User credentials
         */
        if ($this->confirm("Do you have a nickname?")) {
            $nickname = $this->ask("What's your nickname?");
            $password = $this->secret("And what's your password?");
            
        } else {
            $this->line("Let's register you!");
            $this->newLine();

            $success = false;

            do {
                $nickname = $this->ask("How would you like to be called (your nickname)?");
                $this->info("Ok. $nickname will be your nickname.");

                $password = $this->secret("Now choose a password");

                $this->task('Registering user', function () use ($nickname, $password, &$success) {
                    $this->newLine();

                    $response = Http::post(route('users.store'), [
                        'nickname' => $nickname, 'password' => $password
                    ]);

                    if ($response->status() === 201) {
                        $this->info("User registered!");
                        $success = true;
                        return true;
                    } elseif ($response->status() === 409) {
                        //$this->error("The nickname $nickname already exists.");
                        $this->error($response->body());
                        return false;
                    }
                });

                if (!$success) {
                    if ($this->confirm("Would you like to try a different one?")) {
                        $this->line("Let's try again!");
                    } else {
                        Cache::put('credentials', [
                            'nickname' => $nickname,
                            'password' => $password
                        ]);
                        return 1;
                    }
                }
            } while (!$success);
        }

        while (true) {
            /*
             * Menu
             */
            $choice = $this->choice(
                question: "What would you like to do?",
                choices: [
                    "Start a new battle",
                    "Continue battle",
                    "See my infos",
                    "See my battle history",
                    "See ranking",
                    "Exit"
                ]
            );

            switch ($choice) {
                case 'Start a new battle':
                    $ids = [];

                    $this->task("Retrieving heroes...", function () use ($nickname, $password, &$ids) {
                        $this->newLine();

                        $response = Http::withHeaders(compact('nickname', 'password'))->get(route('heroes.index'));

                        if ($response->status() === 200) {
                            $this->table(
                                ['ID', 'Class', 'Life Points', 'Strength', 'Defense', 'Agility', 'Damage'],
                                $response->json()
                            );

                            $ids = Arr::pluck($response->json(), 'id');

                            return true;
                        } else {
                            $this->handleAuthStatusCode($response->status());
                        }
                    });

                    $class_id = $this->ask("Please, inform the ID of the chosen class");

                    if (!in_array($class_id, $ids)) {
                        $this->error("ID {$class_id} isn't an Hero ID. Please, try again and choose a valid ID.");
                        return 1;
                    }

                    $error = false;
                    $fight_id = 0;

                    $this->task(
                        "Searching for a monster...",
                        function () use ($class_id, $nickname, $password, &$error, &$fight_id) {
                            $this->newLine();

                            $response = Http::withHeaders(compact('nickname', 'password'))
                                ->post(route('fights.store'), ['hero_id' => $class_id]);

                            if ($response->status() === 201) {
                                $this->info("Your opponent is a {$response->json()['monster']['class']}!");

                                $fight_id = $response->json()['fight']['id'];

                                return true;
                            } elseif ($response->status() === 409) {
                                $this->error(
                                    "There is already a battle in course. Finish it before starting a new one."
                                );

                                $error = true;

                                return false;
                            } else {
                                $this->handleAuthStatusCode($response->status());

                                $error = true;
                                return false;
                            }
                        }
                    );

                    if (!$error) {
                        $this->line("It's time to fight!");

                        $fight_end = $this->fightTurn($fight_id, ['nickname' => $nickname, 'password' => $password]);

                        if ($fight_end) {
                            return 0;
                        }
                    } else {
                        return 1;
                    }

                    break;

                case 'Continue battle':
                    $fight_id = 0;

                    $this->task('Retrieving battle...', function () use ($nickname, $password, &$fight_id) {
                        $this->newLine();

                        $response = Http::withHeaders(compact('nickname', 'password'))
                            ->get(route('users.fights.index', ['user' => $nickname]));

                        if (!$response->successful()) {
                            $this->handleAuthStatusCode($response->status());
                            return false;
                        } else {
                            $fight = head(Arr::where($response->json(), fn($fight) => is_null($fight['win'])));

                            if (empty($fight)) {
                                $this->info("There's no battle to continue.");
                            } else {
                                $this->info(
                                    "There is a battle of a "
                                    . $fight['hero']['class']
                                    . " against a "
                                    . $fight['monster']['class']
                                );
                                $this->info(
                                    "Your current life points: " . end($fight['log'])['hero_current_life_points']
                                );

                                $fight_id = $fight['id'];
                            }

                            return true;
                        }
                    });

                    if ($fight_id > 0) {
                        if ($this->confirm("Wish to continue this battle?")) {
                            $fight_end = $this->fightTurn($fight_id, ['nickname' => $nickname, 'password' => $password]);

                            if ($fight_end) {
                                return 0;
                            }
                        }
                    }

                    break;

                case 'See my infos':
                    $error = false;

                    $this->task("Retrieving user info...", function () use ($nickname, $password) {
                        $this->newLine();

                        $response = Http::withHeaders(compact('nickname', 'password'))
                            ->get(route('users.show', $nickname));

                        if ($response->status() === 200) {
                            $body = $response->json();

                            $this->info("Here is the information about you:");
                            $this->comment("Nickname: " . $body['nickname']);
                            $this->comment("Score: " . $body['score']);
                            return true;
                        } else {
                            $this->handleAuthStatusCode($response->status());
                            return false;
                        }
                    });

                    break;

                case 'See my battle history':
                    $battle_history = [];

                    $this->task("Retrieving battles...", function () use ($nickname, $password, &$battle_history) {
                        $this->newLine();

                        $response = Http::withHeaders(
                            compact('nickname', 'password')
                        )->get(route('users.fights.index', ['user' => $nickname]));

                        if (!$response->successful()) {
                            $this->handleAuthStatusCode($response->status());
                            return false;
                        }

                        $battle_history = $response->json();

                        return true;
                    });

                    
                    $rows = array_map(
                        function ($battle) {
                            if ($battle['win'] == true) {
                                $result = 'Winner';
                            } elseif ($battle['win'] == false) {
                                $result = 'Looser';
                            } else {
                                $result = null;
                            }
                            
                            $row = [
                                'id' => $battle['id'],
                                'hero' => $battle['hero']['class'],
                                'monster' => $battle['monster']['class'],
                                'result' => $result,
                                'score' => $battle['score']
                            ];

                            return $row;
                        },
                        $battle_history
                    );

                    $this->table(
                        headers: ['ID', 'Hero', 'Monster', 'Result', 'Score'],
                        rows: $rows
                    );
                    break;

                case 'See ranking':
                    $error = false;

                    $this->task('Retrieving ranking', function () use (&$error, $nickname, $password) {
                        $this->newLine();

                        $response = Http::withHeaders(compact('nickname', 'password'))->get(route('users.ranking'));

                        if ($response->ok()) {
                            $this->table(
                                ['Nickname', 'Score'],
                                $response->json()
                            );

                            return true;
                        } else {
                            $this->handleAuthStatusCode($response->status());

                            $error = true;
                            return false;
                        }
                    });

                    if ($error) {
                        return 1;
                    }

                    break;
                case 'Exit':
                    return 0;
                default:
                    return 1;
            }
        }
    }

    private function fightTurn(int $fight_id, array $headers): bool
    {
        $response = null;
        $is_someone_dead = false;

        while (!$is_someone_dead) {
            $choice = $this->choice(
                question: "What will you do?",
                choices: [
                    "Fight",
                    "Exit"
                ]
            );

            if ($choice == "Fight") {
                $this->task("Approching the enemy...", function () use (&$response, $fight_id, $headers) {
                    $response = Http::withHeaders($headers)
                        ->patch(route('fights.update', ['fight' => $fight_id]));

                    if ($response->successful()) {
                        return true;
                    } else {
                        $this->newLine();

                        $this->handleAuthStatusCode($response->status());
                        return false;
                    }
                });

                $content = $response?->json();
                $battle_log = end($content['fight']);

                /*
                 * Initiative
                 */
                $this->line("Time to roll initiative!");
                $this->task("Rolling initiative", function () use ($battle_log) {
                    $this->newLine();
                    $this->line("Your roll is {$battle_log['initiative']['hero_roll']}");
                    $this->info("Your initiative is {$battle_log['initiative']['hero_initiative']}!");

                    return true;
                });

                /*
                 * Attack 1
                 */
                if ($battle_log['attack_1']['attacker'] === 'monster') {
                    $is_someone_dead = $this->monsterAttack($battle_log['attack_1']);
                } else {
                    $is_someone_dead = $this->heroAttack($battle_log['attack_1']);
                }
                
                /*
                 * Attack 2
                 */
                if (!$is_someone_dead) {
                    if ($battle_log['attack_2']['attacker'] === 'monster') {
                        $is_someone_dead = $this->monsterAttack($battle_log['attack_2']);
                    } else {
                        $is_someone_dead = $this->heroAttack($battle_log['attack_2']);
                    }
                }
            } else {
                return false;
            }
        }

        return $is_someone_dead;
    }

    private function monsterAttack(array $battle_log): bool
    {
        $is_someone_dead = false;

        $this->newLine();

        $this->line(
            "Unfortunately, the monster's bloodlust speaks louder than your determination and he attacks before you can react"
        );
        $this->line("Defend youserlf!");
        $this->task("Rolling defense...", function () use ($battle_log) {
            $this->newLine();

            $this->line("The die result is {$battle_log['defender_roll']}");
            $this->info("Your defense roll is {$battle_log['defense_result']}");

            return true;
        });

        if ($battle_log['defense_result'] >= $battle_log['attack_result']) {
            $this->newLine();

            $this->line(
                "Your training proved its worth and you defend yourself with mastery of the creature's rampant attack"
            );
        } else {
            $this->newLine();

            $this->line("Despite your efforts the creature's fury broke through your defenses");
            $this->info("You suffered {$battle_log['damage']} points of damage.");
            $this->info("Your life points now are: {$battle_log['defender_life_points']}");

            if ($battle_log['defender_life_points'] <= 0) {
                $this->error("You died!");
                $is_someone_dead = true;
            }
        }

        return $is_someone_dead;
    }

    private function heroAttack(array $battle_log): bool
    {
        $is_someone_dead = false;

        $this->newLine();

        $this->line(
            "Thanks to your training and combat experience, you were ready to attack long before your opponent realized the danger"
        );
        $this->line("Attack, mighty hero!");
        $this->task("Rolling attack...", function () use ($battle_log) {
            $this->newLine();

            $this->line("The die result is {$battle_log['attacker_roll']}");
            $this->info("Your attack roll is {$battle_log['attack_result']}");

            return true;
        });

        if ($battle_log['defense_result'] >= $battle_log['attack_result']) {
            $this->newLine();

            $this->line(
                "Despite your ability, the creature's reflexes allowed it to defend itself from your onslaught"
            );
        } else {
            $this->newLine();

            $this->line("Despite its best efforts, the creature was unable to escape its attack");
            $this->info("You have caused {$battle_log['damage']} points of damage.");

            if ($battle_log['defender_life_points'] <= 0) {
                $this->info("The monster life points now are: {$battle_log['defender_life_points']}");

                $this->error("You won!");
                $is_someone_dead = true;
            }
        }

        return $is_someone_dead;
    }

    private function handleAuthStatusCode(int $status): void
    {
        $message = match($status) {
            400 => "Nickname and/or password missing. Try again.",
            401 => "Nickname and/or Password incorrect.",
        default => "Unkown error"
        };

            $this->error($message);
    }
}
