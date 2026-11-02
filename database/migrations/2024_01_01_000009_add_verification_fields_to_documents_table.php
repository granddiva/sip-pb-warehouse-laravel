<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('catatan_arsip');
            $table->foreignId('verified_by_user_id')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->string('kondisi_berkas_fisik')->nullable()->after('verified_by_user_id');
            $table->text('catatan_verifikasi')->nullable()->after('kondisi_berkas_fisik');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['verified_by_user_id']);
            $table->dropColumn(['verified_at', 'verified_by_user_id', 'kondisi_berkas_fisik', 'catatan_verifikasi']);
        });
    }
};
