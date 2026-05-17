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
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('identity')->primary();
            $table->string('p_a', 255);
            $table->string('s_a', 255)->nullable();
            $table->string('p_n', 255);
            $table->string('s_n', 255)->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('active');
            $table->boolean('email_active');
            $table->string('otp', 6)->nullable();
            $table->string('api_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
             $table->foreignId('id_rol')
              ->constrained('roles', 'id_rol') // Indica la tabla y su llave primaria personalizada
              ->onUpdate('cascade');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // Enlace explícito a la columna identity de usuarios
            $table->unsignedBigInteger('identity')->nullable()->index();
            $table->foreign('identity')->references('identity')->on('users')->onDelete('cascade');
            //$table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
