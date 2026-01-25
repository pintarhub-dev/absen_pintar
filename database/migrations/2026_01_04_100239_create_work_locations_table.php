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
        Schema::create('work_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('name'); // Contoh: Kantor Pusat, Cabang Surabaya
            $table->string('address')->nullable();

            // Geofencing Logic
            $table->decimal('latitude', 10, 8);  // Titik Koordinat
            $table->decimal('longitude', 11, 8); // Titik Koordinat
            $table->integer('radius')->default(100); // Radius dalam meter (misal: 100m dari titik)
            $table->string('timezone', 50)->default('Asia/Jakarta');

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
        Schema::dropIfExists('work_locations');
    }
};
