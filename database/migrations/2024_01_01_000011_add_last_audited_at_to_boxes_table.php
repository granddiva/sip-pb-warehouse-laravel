<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boxes', function (Blueprint $table) {
            $table->timestamp('last_audited_at')->nullable()->after('catatan');
            $table->foreignId('last_audited_by_user_id')->nullable()->after('last_audited_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('boxes', function (Blueprint $table) {
            $table->dropForeign(['last_audited_by_user_id']);
            $table->dropColumn(['last_audited_at', 'last_audited_by_user_id']);
        });
    }
};
