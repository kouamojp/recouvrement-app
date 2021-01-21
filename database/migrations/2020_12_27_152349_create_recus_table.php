<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recus', function (Blueprint $table) {
            $table->id();
            $table->string('bordereau');
            $table->integer('montant');
            $table->string('mode');
            $table->date('date');

            $table->unsignedBigInteger('debiteur_id');
            $table->foreign('debiteur_id')->references('id')->on('debiteurs')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('dette_id');
            $table->foreign('dette_id')->references('id')->on('dettes')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('partenaire_id');
            $table->foreign('partenaire_id')->references('id')->on('partenaires')->onDelete('cascade')->nullable();
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
        Schema::dropIfExists('recus');
    }
}
