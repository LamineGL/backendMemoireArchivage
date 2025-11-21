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
        Schema::create('log_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('document_id')->nullable()->constrained('documents')->onDelete('cascade');
            $table->enum('type_action', ['ajout', 'modification', 'suppression', 'telechargement', 'consultation', 'restauration', 'partage']);
            $table->json('details_action')->nullable();
            $table->string('hash_blockchain', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_actions');
    }
};
