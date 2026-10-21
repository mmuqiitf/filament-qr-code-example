<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assembly_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('product_name');
            $table->boolean('is_rework')->default(false);
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('bom_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_order_id')->constrained()->cascadeOnDelete();
            $table->string('component_sku');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        Schema::create('assembly_builds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_order_id')->constrained()->cascadeOnDelete();
            $table->string('operator_badge');
            $table->string('operator_name');
            $table->string('rework_reason')->nullable();
            $table->json('components');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assembly_builds');
        Schema::dropIfExists('bom_lines');
        Schema::dropIfExists('assembly_orders');
    }
};
