<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebpartTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debpart', function (Blueprint $table) {
         $table->unsignedBigInteger('debiteur_id');
         $table->foreign('debiteur_id')->references('id')->on('debiteurs')->onDelete('cascade')->nullable();
         $table->unsignedBigInteger('partenaire_id');
         $table->foreign('partenaire_id')->references('id')->on('partenaires')->onDelete('cascade')->nullable();
     });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debpart');
    }
}
