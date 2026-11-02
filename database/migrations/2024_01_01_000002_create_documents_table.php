<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pb')->unique();
            $table->date('tanggal_pb');
            $table->string('bagian');
            $table->string('nama_pemohon');
            $table->string('nip_pemohon')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status')->default('Menunggu Arsip Fisik');
            $table->string('lokasi_rak')->nullable();
            $table->string('nomor_box')->nullable();
            $table->string('dokumen')->nullable();
            $table->string('dokumen_size')->nullable();
            $table->string('dokumen_path')->nullable();
            $table->text('catatan_arsip')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
