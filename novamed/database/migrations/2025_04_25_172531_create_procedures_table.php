<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description');
            $table->decimal('base_price', 8, 2)->unsigned();
            // Klucz obcy do kategorii procedur
            $table->foreignId('procedure_category_id')
                ->nullable() // Może być procedura bez kategorii? Zdecyduj.
                ->constrained('procedure_categories') // Powiązanie z tabelą 'procedure_categories'
                ->nullOnDelete(); // Jeśli kategoria zostanie usunięta, ustaw FK na NULL (lub cascadeOnDelete?)
            $table->text('recovery_timeline_info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }

};
