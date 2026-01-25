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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();

            // Durasi Cuti
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days')->default(1); // Total hari yang dipotong

            // Detail
            $table->text('reason'); // Alasan cuti
            $table->string('attachment')->nullable(); // Foto surat dokter / undangan nikah

            // Status Approval
            $table->enum('status', ['pending', 'approved_by_supervisor', 'approved_by_manager', 'approved_by_hr', 'rejected', 'cancelled'])->default('pending');

            // Jejak Approval (Siapa yang ACC?)
            $table->unsignedBigInteger('approved_by')->nullable(); // User ID Manager/HR yang final approve
            $table->dateTime('approved_at')->nullable();

            $table->text('rejection_reason')->nullable(); // Kalau ditolak, kenapa?

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
        Schema::dropIfExists('leave_requests');
    }
};
