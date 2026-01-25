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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Lokasi "Base" karyawan (bisa null jika dia full remote/mobile murni)
            $table->foreignId('work_location_id')->nullable()->constrained()->nullOnDelete();
            // false = Wajib di lokasi (Statis)
            // true = Bebas dimana saja (Mobile/Kurir)
            $table->boolean('is_flexible_location')->default(false);
            $table->string('registered_device_id')->nullable();
            $table->string('nik', 20)->nullable();
            $table->string('full_name', 150);
            $table->string('nickname', 20)->nullable();
            $table->string('place_of_birth', 50);
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->string('phone', 20);
            $table->text('address');
            $table->string('identity_number'); // No KTP (NIK Kependudukan) -> Penting untuk BPJS
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->date('join_date')->nullable();
            $table->enum('employment_status', [
                'probation',
                'contract',
                'permanent',
                'internship',
                'freelance',
                'resigned',
                'terminated',
                'retired'
            ])->default('probation');
            $table->boolean('is_access_web')->default(false); // Penanda apakah dia bisa akses web untuk keperluan approve atau fitur web
            $table->boolean('is_attendance_required')->default(true); // Penanda apakah dia Wajib Absen?
            $table->date('resignation_date')->nullable();
            $table->text('resignation_note')->nullable();

            $table->foreignId('employee_id_supervisor')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('employee_id_manager')->nullable()->constrained('employees')->nullOnDelete();

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
        Schema::dropIfExists('employees');
    }
};
