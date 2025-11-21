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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 200);
            $table->text('description')->nullable();
            $table->string('chemin_fichier', 255);
            $table->string('nom_fichier_original', 255);
            $table->bigInteger('file_size')->unsigned()->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->foreignId('type_document_id')->nullable()->constrained('type_documents')->onDelete('set null');
            $table->foreignId('departement_id')->constrained('departements')->onDelete('cascade');
            $table->foreignId('user_createur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('version')->default(1);
            $table->text('mots_cles')->nullable();
            $table->enum('statut', ['actif', 'archive', 'supprime'])->default('actif');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
