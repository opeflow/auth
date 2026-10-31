<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('log_event', function (Blueprint $table): void {
            $table->unsignedBigInteger('system_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('log_event', function (Blueprint $table): void {
            $table->dropColumn('system_id');
        });
    }
};
