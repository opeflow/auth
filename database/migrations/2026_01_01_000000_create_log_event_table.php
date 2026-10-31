<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('log_event', function (Blueprint $table): void {
            $table->bigIncrements('id'); $table->timestampTz('created_at')->useCurrent();
            $table->string('correlation_id',64)->nullable(); $table->string('event_type',64); $table->string('level',16);
            $table->string('subject')->nullable(); $table->string('client_ip',64)->nullable(); $table->string('http_method',16)->nullable();
            $table->string('http_path',2048)->nullable(); $table->integer('http_status')->nullable(); $table->integer('latency_ms')->nullable();
            $table->jsonb('details_json')->nullable(); $table->index('created_at'); $table->index('event_type'); $table->index('subject');
        });
    }
    public function down(): void { Schema::dropIfExists('log_event'); }
};
