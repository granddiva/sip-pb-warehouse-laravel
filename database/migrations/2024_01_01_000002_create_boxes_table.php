<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id')->constrained('racks')->cascadeOnDelete();
            $table->string('nomor_box');
            $table->integer('baris_ke')->default(1);
            $table->integer('kolom_ke')->default(1);
            $table->integer('kapasitas_maksimal')->default(20);
            $table->string('status')->default('Tersedia');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['rack_id', 'nomor_box']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boxes');
    }
};
