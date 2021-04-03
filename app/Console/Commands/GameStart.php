<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

use function GuzzleHttp\Promise\task;

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
                    $response = Http::post(route('users.store'), [
                        compact('nickname', 'password')
                    ]);
        
                    if ($response->status() === 201) {
                        $this->info("User registered!");
                        $success = true;
                        return true;
                    } elseif ($response->status() === 409) {
                        $this->error("The nickname $nickname alredy exists.");
                        return false;
                    }
                });

                if (!$success) {
                    if ($this->confirm("Would you like to try a different one?")) {
                        $this->line("Let's try again!");
                    } else {
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
                    "See my infos",
                    "See my battle history",
                    "See ranking",
                    "Exit"
                ]
            );

            switch ($choice) {
                case 'See my infos':
                    $error = false;
    
                    $this->task("Retrieving user info", function () use ($nickname, $password) {
                        $response = Http::withHeaders([
                            compact('nickname', 'password')
                        ])->get(route('users.show', $nickname));
    
                        if ($response->status() === 200) {
                            $body = $response->json();
                            
                            $this->info("Here is the information about you:");
                            $this->comment("Nickname: " . $body['nickname']);
                            $this->comment("Score: " . $body['score']);
                            return true;
                        }
                    });
    
                    break;
                case 'See ranking':
                    $error = false;
    
                    $this->task('Retrieving ranking', function () use (&$error) {
                        $response = Http::get(route('users.ranking'));
                        
                        if ($response->ok()) {
                            $this->table(
                                ['Nickname', 'Score'],
                                $response->json()
                            );

                            return true;
                        } else {
                            match($response->status()) {
                                400 => $this->error("Nickname and/or password missing. Try again."),
                                401 => $this->error("Nickname and/or Password incorrect.")
                            };

                            $error = true;
                            return false;
                        }
                    });
    
                    if ($error) return 1;
    
                    break;
                case 'Exit':
                    return 0;
                    break;
                default:
                    # code...
                    break;
            }
        }
        


        return 0;
    }
}
