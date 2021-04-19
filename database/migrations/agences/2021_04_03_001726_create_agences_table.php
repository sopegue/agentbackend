<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('adresse_id')->nullable();
            $table->string('email')->unique();
            $table->string('name');
            $table->enum('type', ['private', 'public', 'personnal']);
            $table->enum('old_agence', ['yes', 'no']);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::table('agences', function (Blueprint $table) {
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
        Schema::dropIfExists('agences');
    }
}
