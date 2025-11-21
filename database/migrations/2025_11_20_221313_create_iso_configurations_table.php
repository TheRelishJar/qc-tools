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
        Schema::create('iso_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('iso_class')->unique(); // e.g., "1.2.1", "1.-.3", "-.4.-"
            $table->string('particulate_class'); // Can be 1, 2, 3, or '-'
            $table->string('water_class'); // Can be 1, 2, 3, 4, 5, or '-'
            $table->string('oil_class'); // Can be 0, 1, 2, 3, 4, or '-'
            $table->string('compressor'); // QOF or OIS
            $table->string('qas1')->nullable();
            $table->string('qas2')->nullable();
            $table->string('qas3')->nullable();
            $table->string('qas4')->nullable();
            $table->string('qas5')->nullable();
            $table->string('qas6')->nullable();
            $table->string('qas7')->nullable();
            $table->string('qas8')->nullable();
            $table->string('qas9')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iso_configurations');
    }
};