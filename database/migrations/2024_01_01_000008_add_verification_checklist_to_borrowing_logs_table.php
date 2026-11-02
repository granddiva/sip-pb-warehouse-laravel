<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowing_logs', function (Blueprint $table) {
            $table->boolean('is_form_pb_lengkap')->default(true)->after('status_pinjam');
            $table->boolean('is_lampiran_lengkap')->default(true)->after('is_form_pb_lengkap');
            $table->string('kondisi_fisik')->default('Baik & Utuh')->after('is_lampiran_lengkap');
            $table->foreignId('returned_to_user_id')->nullable()->after('handled_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('borrowing_logs', function (Blueprint $table) {
            $table->dropForeign(['returned_to_user_id']);
            $table->dropColumn(['is_form_pb_lengkap', 'is_lampiran_lengkap', 'kondisi_fisik', 'returned_to_user_id']);
        });
    }
};
