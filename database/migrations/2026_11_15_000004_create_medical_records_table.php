<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id(); $table->foreignId('pasien_id')->constrained('pasiens')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->dateTime('visited_at'); $table->text('complaint'); $table->text('diagnosis');
            $table->text('treatment')->nullable(); $table->text('prescription')->nullable();
            $table->string('blood_pressure', 20)->nullable(); $table->decimal('weight_kg', 5, 2)->nullable();
            $table->text('follow_up')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('medical_records'); }
};
