<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ClockInRequest;
use App\Http\Requests\Api\V1\ClockOutRequest;
use App\Http\Resources\Api\V1\AttendanceResource;
use App\Models\AttendanceSummary;
use App\Models\EmployeeScheduleAssignment;
use App\Models\ScheduleOverride;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    /**
     * API Clock In
     */
    public function clockIn(ClockInRequest $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return $this->errorResponse('Data Karyawan tidak ditemukan.', 404);
        }

        // Cek Device Lock
        if ($request->device_id) {
            if (is_null($employee->registered_device_id)) {
                $employee->update(['registered_device_id' => $request->device_id]);
            } else if ($employee->registered_device_id !== $request->device_id) {
                return $this->errorResponse('Anda menggunakan HP baru. Silakan hubungi HRD untuk reset device.', 403);
            }
        }



        $timezone = $employee->workLocation->timezone ?? 'Asia/Jakarta';
        $now = now();
        $today = $now->toDateString();

        // ------------------------------------------------------------------
        // LOGIC BARU: Validasi Jadwal & Tanggal Efektif
        // ------------------------------------------------------------------

        $scheduleId = null;
        $scheduleIn = null;
        $scheduleOut = null;
        $isFlexibleShift = false;
        $shiftFound = false;

        // PRIORITAS 1: Cek apakah ada Override / Tukar Shift untuk TANGGAL INI?
        $dailySchedule = ScheduleOverride::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if ($dailySchedule) {
            // Jika ada override, pakai shift dari override tersebut
            $shift = $dailySchedule->shift; // Relasi ke model Shift
            if (!$shift) {
                return $this->errorResponse('Anda diliburkan untuk hari ini.', 403);
            }
            $shiftFound = true;
        } else {
            // PRIORITAS 2: Cek Jadwal Reguler (Pattern) berdasarkan TANGGAL EFEKTIF
            $assignment = EmployeeScheduleAssignment::with(['schedulePattern.details.shift']) // Eager Load biar cepat
                ->where('employee_id', $employee->id)
                ->whereDate('effective_date', '<=', $today)
                ->orderBy('effective_date', 'desc')
                ->first();

            if (!$assignment) {
                // Jika tidak ada assignment sama sekali, atau tanggal efektif belum tercapai
                return $this->errorResponse('Jadwal kerja belum aktif atau belum ditentukan. (Cek Tanggal Efektif)', 403);
            }

            // HITUNG SHIFT BERDASARKAN PATTERN
            // Hari ini jatuh di urutan ke berapa dalam pattern
            // Rumus: (Selisih Hari Ini dengan Tgl Efektif) Modulo (Panjang Pattern)
            $shift = $assignment->getShiftOnDate($today);
            if ($shift) {
                $shiftFound = true;
            } else {
                // Jika trait mengembalikan null, artinya hari ini jatahnya OFF (Libur)
                return $this->errorResponse('Hari ini adalah jadwal Libur (Off Day) Anda sesuai pola kerja.', 403);
            }
        }

        // Jika setelah semua pengecekan shift masih tidak ketemu
        if (!$shiftFound || !isset($shift)) {
            return $this->errorResponse('Konfigurasi jadwal tidak valid. Hubungi HRD.', 403);
        }

        // SET DATA SHIFT KE VARIABEL
        $scheduleId = $shift->id;
        $isFlexibleShift = $shift->is_flexible;

        if (!$isFlexibleShift) {
            $scheduleIn  = $shift->start_time;
            $scheduleOut = $shift->end_time;
        }

        // 1. Ambil Summary Hari Ini
        $summary = AttendanceSummary::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $today
            ],
            [
                'tenant_id' => $employee->tenant_id,
                'schedule_id'  => $scheduleId,
                'schedule_in'  => $scheduleIn,
                'schedule_out' => $scheduleOut,
                'status' => 'alpha',
                'late_minutes' => 0
            ]
        );

        // 2. Cek Sesi Gantung
        $openSession = $summary->details()->whereNull('clock_out_time')->first();
        if ($openSession) {
            return $this->errorResponse('Anda masih memiliki sesi aktif. Silakan Clock Out terlebih dahulu.', 400);
        }

        // Validasi Sesi Kedua untuk Karyawan Kantor
        if (! $employee->is_flexible_location && $summary->details()->exists()) {
            if (!$openSession) {
                return $this->errorResponse('Anda karyawan kantor, hanya diperbolehkan 1x Sesi Absen per hari.', 400);
            }
        }

        // ---------------------------------------------------------
        // LOGIC LOKASI
        // ---------------------------------------------------------
        $currentLocationId = $employee->work_location_id;

        if ($employee->is_flexible_location) {
            $locations = \App\Models\WorkLocation::all();
            foreach ($locations as $loc) {
                $distance = $this->calculateDistance(
                    $request->latitude,
                    $request->longitude,
                    $loc->latitude,
                    $loc->longitude
                );
                if ($distance <= $loc->radius) {
                    $currentLocationId = $loc->id;
                    break;
                }
            }
        } else {
            $workLocation = $employee->workLocation;
            if (!$workLocation) {
                return $this->errorResponse('Lokasi kerja belum diatur.', 400);
            }
            $distance = $this->calculateDistance(
                $request->latitude,
                $request->longitude,
                $workLocation->latitude,
                $workLocation->longitude
            );
            if ($distance > $workLocation->radius) {
                return response()->json([
                    'success' => false,
                    'message' => 'Di luar jangkauan kantor.',
                    'meta' => [
                        'distance' => round($distance) . ' meter',
                        'allowed_radius' => $workLocation->radius . ' meter'
                    ]
                ], 422);
            }
        }

        // ---------------------------------------------------------
        // LOGIC HITUNG STATUS & KETERLAMBATAN
        // ---------------------------------------------------------

        $isFirstSession = $summary->details()->count() === 0;
        $status = 'present';
        $lateMinutes = 0;

        // Jika bukan sesi pertama, status mengikuti status sebelumnya (jangan diubah jadi present lagi kalau udah late)
        if (!$isFirstSession) {
            $status = $summary->status;
            $lateMinutes = $summary->late_minutes;
        }

        // Logic Hitung Telat (Hanya jalan di Sesi Pertama & Shift Tidak Flexible)
        if ($isFirstSession && $summary->schedule_in && !$isFlexibleShift) {

            $dateString = $summary->date instanceof Carbon
                ? $summary->date->format('Y-m-d')
                : $summary->date;

            $timeString = Carbon::parse($summary->schedule_in)->format('H:i:s');
            $scheduleIn = Carbon::parse("$dateString $timeString", $timezone);

            $tolerance = $shift->late_tolerance_minutes ?? 0;
            $scheduleInWithTolerance = $scheduleIn->copy()->addMinutes($tolerance);

            if ($now->greaterThan($scheduleInWithTolerance)) {
                $status = 'late';
                $lateMinutes = (int) $now->diffInMinutes($scheduleIn);
            } else {
                $status = 'present';
            }
        }

        // ---------------------------------------------------------
        // UPLOAD IMAGE
        // ---------------------------------------------------------
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'clock_in_' . $employee->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $folder = 'attendance/' . $employee->tenant_id . '/' . date('Y-m');
            $imagePath = $file->storeAs($folder, $filename, 'public');
        }

        // CREATE DETAILS
        $summary->details()->create([
            'work_location_id'   => $currentLocationId,
            'clock_in_time'      => $now->toTimeString(),
            'clock_in_latitude'  => $request->latitude,
            'clock_in_longitude' => $request->longitude,
            'clock_in_image'     => $imagePath,
        ]);

        $updateData = [
            'status' => $status,
        ];

        if ($isFirstSession) {
            $updateData['clock_in'] = $now->toTimeString();
            $updateData['clock_in_latitude'] = $request->latitude;
            $updateData['clock_in_longitude'] = $request->longitude;
            $updateData['clock_in_device_id'] = $request->device_id;
            $updateData['clock_in_image'] = $imagePath;
            $updateData['late_minutes'] = $lateMinutes;
        }

        $updateData['clock_out'] = null;

        $summary->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil Clock In (Sesi baru dimulai)',
            'data'    => new AttendanceResource($summary->refresh())
        ]);
    }

    /**
     * API Clock Out
     */
    public function clockOut(ClockOutRequest $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        $now = now();
        $today = $now->toDateString();

        if (!$employee) {
            return $this->errorResponse('Data Karyawan tidak ditemukan.', 404);
        }

        // 2. Cari Summary Hari Ini
        $summary = AttendanceSummary::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$summary) {
            return $this->errorResponse('Anda belum melakukan Clock In hari ini.', 400);
        }

        // 3. Cari Sesi Aktif (Detail yang clock_out_time nya masih NULL)
        $activeSession = $summary->details()
            ->whereNull('clock_out_time')
            ->latest()
            ->first();

        if (!$activeSession) {
            return $this->errorResponse('Tidak ada sesi aktif. Anda mungkin sudah Clock Out sebelumnya.', 400);
        }

        // ---------------------------------------------------------
        // LOGIC UPLOAD IMAGE CLOCK OUT
        // ---------------------------------------------------------
        $imagePath = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            // Format nama: clock_out_{id}_{timestamp}.jpg
            $filename = 'clock_out_' . $employee->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $folder = 'attendance/' . $employee->tenant_id . '/' . $now->format('Y-m');
            $imagePath = $file->storeAs($folder, $filename, 'public');
        }

        // 4. Update DETAILS (Tutup Sesi Ini)
        // Sekarang $imagePath sudah berisi string path (atau null)
        $activeSession->update([
            'clock_out_time'      => $now->toTimeString(),
            'clock_out_latitude'  => $request->latitude,
            'clock_out_longitude' => $request->longitude,
            'clock_out_device_id' => $request->device_id, // Log device saat pulang
            'clock_out_image'     => $imagePath,
        ]);

        // 5. Update SUMMARY (Induk)
        // Summary selalu mencatat jam pulang & lokasi TERAKHIR hari itu
        $summary->update([
            'clock_out'           => $now->toTimeString(),
            'clock_out_latitude'  => $request->latitude,
            'clock_out_longitude' => $request->longitude,
            'clock_out_device_id' => $request->device_id,
            'clock_out_image'     => $imagePath,
            // Status update (opsional): Bisa tambah logic 'early_leave' disini nanti
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil Clock Out (Sesi Ditutup)',
            'data'    => new AttendanceResource($summary->refresh())
        ]);
    }

    // Helper Response
    private function errorResponse($message, $code)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }

    // Helper Haversine (Bisa dipindah ke Trait/Service terpisah nanti)
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
