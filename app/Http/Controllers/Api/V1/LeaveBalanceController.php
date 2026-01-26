<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 404);
        }

        // Ambil saldo tahun ini
        $currentYear = now()->year;

        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->get()
            ->map(function ($balance) {
                return [
                    'leave_type_name' => $balance->leaveType->name,
                    'code' => $balance->leaveType->code,
                    'entitlement' => $balance->entitlement, // Jatah Awal
                    'taken' => $balance->taken,             // Sudah Dipakai
                    'remaining' => $balance->remaining,     // Sisa
                    'deducts_quota' => $balance->leaveType->deducts_quota, // Info: Potong kuota gak?
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $balances,
        ]);
    }
}
