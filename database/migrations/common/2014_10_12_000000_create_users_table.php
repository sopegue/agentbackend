<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('adresse_id')->nullable();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('phone')->nullable();
            $table->string('picture_name')->nullable();
            $table->string('picture_link')->nullable();
            $table->enum('role', ['admin', 'agent', 'client']);
            $table->enum('update_saved', ['yes', 'no'])->default('no');
            $table->enum('newsletter', ['yes', 'no'])->default('no');
            $table->enum('email_me_notif', ['yes', 'no'])->default('no');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->enum('status', ['active', 'verifying', 'deleted']);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
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
        Schema::dropIfExists('users');
    }
}
