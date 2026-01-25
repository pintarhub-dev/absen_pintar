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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('name'); // Contoh: "Cuti Tahunan", "Sakit dengan Surat Dokter", "Unpaid Leave"
            $table->string('code')->nullable(); // AL (Annual Leave), SL (Sick Leave)
            $table->integer('min_months_of_service')->default(0); // Minimal bulan untuk bisa akses cuti ini
            $table->integer('default_quota')->default(12); // Jatah default per tahun (misal: 12)
            $table->boolean('is_paid')->default(true); // True = Digaji penuh, False = Potong gaji
            $table->boolean('is_carry_forward')->default(false); // Boleh dibawa ke tahun depan?
            $table->boolean('requires_file')->default(false); // Wajib upload surat dokter?
            $table->boolean('deducts_quota')->default(true); // Memotong saldo?

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
        Schema::dropIfExists('leave_types');
    }
};
