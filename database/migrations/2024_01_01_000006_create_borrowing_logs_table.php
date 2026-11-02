<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_peminjam');
            $table->string('departemen_peminjam');
            $table->string('keperluan');
            $table->date('tanggal_pinjam');
            $table->date('batas_kembali');
            $table->date('tanggal_kembali')->nullable();
            $table->string('status_pinjam')->default('Dipinjam'); // 'Dipinjam', 'Dikembalikan', 'Terlambat'
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'status_pinjam', 'batas_kembali']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowing_logs');
    }
};
