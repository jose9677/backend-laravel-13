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
        Schema::create('users_actions', function (Blueprint $table) {
            $table->timestamps();

            $table->foreignId('identity')
                  ->constrained('users', 'identity')
                  ->onUpdate('cascade');
                  
            $table->foreignId('id_action')
                  ->constrained('actions', 'id_action')
                  ->onUpdate('cascade');

            $table->primary(['identity', 'id_action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_actions');
    }
};
