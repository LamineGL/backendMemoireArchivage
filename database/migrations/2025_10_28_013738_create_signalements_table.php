<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('signalements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('chef_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('departement_id')->constrained('departements')->onDelete('cascade');
            $table->enum('motif', ['il archive trop de documents qui ne sont pas de l entreprise', 'non_respect_procedures',  'inactivite_prolongee', 'autre']);
            $table->text('details');
            $table->enum('statut', ['en_attente', 'en_cours', 'traite', 'rejete'])->default('en_attente');
            $table->text('reponse_admin')->nullable();
            $table->timestamp('traite_le')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
