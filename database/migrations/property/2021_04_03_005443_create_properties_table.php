<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['Studio', 'Maison', 'Appartement', 'Villa', 'Haut-Standing', 'Bureau', 'Magasin', 'Terrain']);
            $table->string('taille');
            $table->unsignedBigInteger('adresse_id');
            $table->string('price_fixed')->nullable();
            $table->string('price_min')->nullable();
            $table->string('price_max')->nullable();
            $table->enum('negociable', ['yes', 'no'])->default('no');
            $table->enum('proposition', ['Achat total', 'Acheter une part', 'Location totale', 'Louer une part']);
            $table->longText('informations');
            $table->unsignedInteger('bed')->nullable();
            $table->unsignedInteger('bath')->nullable();
            $table->unsignedInteger('garage')->nullable();
            $table->timestamps();
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('adresse_id')->references('id')->on('adresses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('properties');
    }
}
