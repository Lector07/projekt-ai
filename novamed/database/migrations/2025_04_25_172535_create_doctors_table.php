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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('specialization')->index();
            $table->text('bio')->nullable();
            $table->string('profile_picture_path')->nullable();
            $table->decimal('price_modifier', 3, 2)->nullable()->default(1.00);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void { Schema::dropIfExists('doctors'); }

};
