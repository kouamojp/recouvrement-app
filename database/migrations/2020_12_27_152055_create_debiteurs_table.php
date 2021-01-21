<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebiteursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debiteurs', function (Blueprint $table) {
            $table->id();
            $table->string('societe_debitrice');
            $table->string('gerant');
            $table->string('localisation');
            $table->string('ville');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('telephone');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debiteurs');
    }
}
