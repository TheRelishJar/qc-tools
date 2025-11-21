<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('water_class');
            $table->string('product_range');
            $table->string('dewpoint')->nullable();
            $table->decimal('min_flow', 10, 2);
            $table->decimal('max_flow', 10, 2);
            $table->string('inlet_filters')->nullable();
            $table->string('outlet_filters')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ranges');
    }
};