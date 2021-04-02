<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:setup
                            {--host=127.0.0.1 : The host address to serve the game on}
                            {--port=8000 : The port to serve the game on}
                            {--no-server : Configure the Database but do not starts the server}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Do the initial setup for the game.';

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
        $host = $this->option('host');
        $port = $this->option('port');
        $no_server = $this->option('no-server');

        $this->line("Preparing database...");

        try {
            $is_database_filled = DB::table('characters')->exists()
                || DB::table('users')->exists()
                || DB::table('fights')->exists();
        } catch (QueryException $e) {
            Str::contains(
                $e->getMessage(),
                'Base table or view not found: 1146'
            ) ? Artisan::call('migrate') : throw $e;

            $is_database_filled = false;
        }

        if (!$is_database_filled) {
            Artisan::call('db:seed');
        } else {
            if ($this->confirm(
                'Database for the game alredy exists. Do you wish to overwrite it? [y - yes | n - no]:'
            )) {
                Artisan::call('migrate:fresh');
                Artisan::call('db:seed');
            }
        }
        
        if (!$no_server) {
            $this->line('Starting game server...');
            $this->info("Game started at http://{$host}:{$port}");
            
            Artisan::call('serve', ['--host' => $host, '--port' => $port]);
        }

        return 0;
    }
}
