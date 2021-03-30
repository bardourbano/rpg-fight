<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCharactersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->enum('type', ['hero', 'monster']);
            $table->string('class');
            $table->unsignedTinyInteger('life_points');
            $table->unsignedTinyInteger('strength');
            $table->unsignedTinyInteger('defense');
            $table->unsignedTinyInteger('agility');
            $table->unsignedTinyInteger('damage_factor');
            $table->enum('damage_dice', ['d2', 'd4', 'd6', 'd8']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('characters');
    }
}
