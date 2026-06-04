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
        Schema::create('actions', function (Blueprint $table) {
            $table->id('id_action');
            $table->string('description', 255);
            $table->boolean('active');

            $table->foreignId('id_module')
            ->constrained('modules', 'id_module') // Indica la tabla y su llave primaria personalizada
            ->onUpdate('cascade');

            $table->string('route', 255)->nullable()->after('id_module');
            $table->string('method', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actions');
    }
};
