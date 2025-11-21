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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('chemin_backup', 255);
            $table->bigInteger('taille_backup')->unsigned()->nullable();
            $table->foreignId('effectue_par')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('statut', ['en_cours', 'termine', 'echoue'])->default('en_cours');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
