<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonagensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('personagens', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->enum('tipo', ['heroi', 'monstro']);
            $table->string('classe');
            $table->unsignedTinyInteger('pontos_vida');
            $table->unsignedTinyInteger('forca');
            $table->unsignedTinyInteger('defesa');
            $table->unsignedTinyInteger('agilidade');
            $table->unsignedTinyInteger('fator_dano');
            $table->enum('dado_dano', ['d2', 'd4', 'd6', 'd8']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('personagens');
    }
}
