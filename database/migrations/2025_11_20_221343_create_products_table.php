<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('flow_range')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('refrigerant_dryer_note')->nullable();
            $table->text('desiccant_dryer_note')->nullable();
            $table->text('qaf_note')->nullable();
            $table->string('category')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};