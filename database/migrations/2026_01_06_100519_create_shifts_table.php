<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('name'); // Contoh: "Shift Staff Gudang", "Shift Programmer"
            // Menandakan shift ini adalah shift libur (Off Day)
            // Berguna untuk pattern misal: 5 Kerja, 2 Libur (Shift ID Libur dipanggil)
            $table->boolean('is_day_off')->default(false);
            // Jika true, abaikan start_time/end_time, fokus ke daily_target_minutes
            $table->boolean('is_flexible')->default(false);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('break_duration_minutes')->default(60);
            // TARGET DURASI (Penting untuk Flexible & Hitung Gaji)
            // Misal: 480 menit (8 Jam).
            // Untuk shift biasa, ini otomatis dihitung dari selisih start & end.
            // Untuk shift flexible, ini diinput manual oleh HRD.
            $table->integer('daily_target_minutes')->nullable();

            // ATURAN TOLERANSI (Optional)
            // Berapa menit boleh telat sebelum dipotong gaji? (Misal: 15 menit)
            $table->integer('late_tolerance_minutes')->default(0);

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
