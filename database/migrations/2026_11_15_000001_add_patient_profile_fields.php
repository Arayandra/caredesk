<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pasiens', function (Blueprint $table) {
            $table->string('kode_pasien', 20)->nullable()->unique();
            $table->string('jenis_kelamin', 20)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('golongan_darah', 5)->nullable();
            $table->text('alergi')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('pasiens', function (Blueprint $table) {
            $table->dropUnique(['kode_pasien']);
            $table->dropColumn(['kode_pasien', 'jenis_kelamin', 'tanggal_lahir', 'golongan_darah', 'alergi']);
        });
    }
};
