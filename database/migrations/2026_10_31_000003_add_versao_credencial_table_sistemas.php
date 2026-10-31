<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sistemas', function (Blueprint $table): void {
            $table->boolean('cadastro_habilitado')->default(true);
            $table->unsignedInteger('versao_credencial')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('sistemas', function (Blueprint $table): void {
            $table->dropColumn(['cadastro_habilitado', 'versao_credencial']);
        });
    }
};
