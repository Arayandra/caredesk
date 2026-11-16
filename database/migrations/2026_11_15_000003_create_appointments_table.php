<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id(); $table->foreignId('pasien_id')->constrained('pasiens')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->string('service_name'); $table->dateTime('scheduled_at');
            $table->string('status', 24)->default('Menunggu'); $table->string('reason', 500)->nullable();
            $table->text('internal_note')->nullable(); $table->timestamps();
            $table->index(['scheduled_at', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('appointments'); }
};
