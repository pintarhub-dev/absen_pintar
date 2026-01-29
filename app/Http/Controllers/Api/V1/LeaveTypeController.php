<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['message' => 'Employee data not found'], 404);
        }

        // 1. Hitung Masa Kerja Karyawan (dalam Bulan)
        // Ini penting untuk validasi kolom 'min_months_of_service'
        $tenureMonths = 0;
        if ($employee->join_date) {
            $tenureMonths = Carbon::parse($employee->join_date)->diffInMonths(Carbon::now());
        }

        // 2. Query Leave Types
        // Filter berdasarkan Tenant ID karyawan tersebut
        $leaveTypes = LeaveType::where('tenant_id', $employee->tenant_id)
            ->orderBy('category', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($type) use ($tenureMonths) {

                // 3. Logic Kelayakan (Eligibility)
                // Apakah karyawan sudah bekerja cukup lama untuk jenis cuti ini?
                $isEligible = $tenureMonths >= $type->min_months_of_service;

                return [
                    'id'            => $type->id,
                    'name'          => $type->name,          // e.g. "Cuti Tahunan", "Sakit dengan Surat Dokter"
                    'code'          => $type->code,          // e.g. "CT", "S-SD"
                    'category'      => $type->category,      // 'leave', 'sick', 'permit'

                    // Frontend Logic Flags
                    'requires_file' => (bool) $type->requires_file,  // Jika true, frontend harus show tombol upload & set required
                    'deducts_quota' => (bool) $type->deducts_quota,  // Info user: "Ini motong jatah cuti lho"
                    'is_paid'       => (bool) $type->is_paid,        // Info user: "Tenang, ini dibayar"

                    // Validation Logic
                    'is_eligible'           => $isEligible,
                    'min_months_required'   => $type->min_months_of_service,
                    'current_tenure_months' => $tenureMonths,
                    'eligibility_message'   => $isEligible
                        ? 'Available'
                        : "Butuh masa kerja minimal {$type->min_months_of_service} bulan. (Anda: {$tenureMonths} bulan)",
                ];
            });

        return response()->json([
            'message' => 'Jenis cuti berhasil diambil',
            'data'    => $leaveTypes,
        ], 200);
    }
}
