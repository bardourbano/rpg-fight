<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
    protected $description = 'Faz o setup inicial do jogo.';

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
        try {
            $is_database_filled = DB::table('characters')->exists()
                && DB::table('users')->exists()
                && DB::table('fights')->exists();
        } catch (QueryException $e) {
            Str::contains($e->getMessage(), 'Base table or view not found: 1146') ? Artisan::call('migrate') : throw $e;
            $is_database_filled = false;
        }

        if (!$is_database_filled) {
            Artisan::call('db:seed');
        } else {
            if ($this->confirm('Já existe uma base de dados, deseja sobrescrevê-la? [y - sim | n - não]:')) {
                Artisan::call('migrate:fresh');
                Artisan::call('db:seed');
            }
        }

        return 0;
    }
}
