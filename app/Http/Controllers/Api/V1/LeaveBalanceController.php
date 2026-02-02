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

        $currentYear = now()->year;

        // Ambil saldo, sekalian cek jika ada saldo tahun depan (opsional)
        // Kita filter tahun ini saja sesuai request
        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $currentYear)
            ->get()
            ->map(function ($balance) {
                return [
                    'id' => $balance->id, // Penting buat referensi update
                    'leave_type_name' => $balance->leaveType->name,
                    'code' => $balance->leaveType->code,
                    'entitlement' => (int) $balance->entitlement,
                    'carried_over' => (int) $balance->carried_over,
                    'taken' => (int) $balance->taken,
                    'remaining' => (int) $balance->remaining, // Ini otomatis dari Virtual Column
                    'deducts_quota' => (bool) $balance->leaveType->deducts_quota,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $balances,
        ]);
    }
}
