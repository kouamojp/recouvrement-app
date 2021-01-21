<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDettesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dettes', function (Blueprint $table) {
            $table->id();
            $table->string('intitule');
            $table->integer('montant_reclame');
            $table->integer('montant_reconnu');
            $table->integer('montant_verse');
            $table->integer('solde');
            $table->date('dernier_versement', $precision = 0);

            $table->unsignedBigInteger('debiteur_id');
            $table->foreign('debiteur_id')->references('id')->on('debiteurs')->onDelete('cascade')->nullable();
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
        Schema::dropIfExists('dettes');
    }
}
