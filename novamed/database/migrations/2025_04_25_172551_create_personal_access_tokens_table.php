<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); // Klucz główny
            $table->morphs('tokenable');
            $table->string('name'); // Nazwa nadana tokenowi przez użytkownika/aplikację
            $table->string('token', 64)->unique(); // Unikalny hash tokenu (oryginalny token jest pokazywany tylko raz)
            $table->text('abilities')->nullable(); // Przechowuje uprawnienia/zakresy tokenu (często jako JSON)
            $table->timestamp('last_used_at')->nullable(); // Kiedy token był ostatnio użyty
            $table->timestamp('expires_at')->nullable(); // Kiedy token wygasa (jeśli ustawiono)
            $table->timestamps(); // created_at i updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
