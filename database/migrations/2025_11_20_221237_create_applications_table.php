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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('particulate_class'); // Can be 1, 2, 3, or '-'
            $table->string('water_class'); // Can be 1, 2, 3, 4, 5, or '-'
            $table->string('oil_class'); // Can be 0, 1, 2, 3, 4, or '-'
            $table->timestamps();
            
            // Ensure unique application names within an industry
            $table->unique(['industry_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};