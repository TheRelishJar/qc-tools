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
        Schema::create('iso_purity_levels', function (Blueprint $table) {
            $table->id();
            $table->string('iso_class_type'); // 'particle', 'water', or 'oil'
            $table->string('level'); // 1, 2, 3, 4, 5, 0, or '-'
            $table->text('purity_description');
            $table->timestamps();
            
            // Ensure unique combinations
            $table->unique(['iso_class_type', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iso_purity_levels');
    }
};