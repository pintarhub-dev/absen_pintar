<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\Shift;

trait HasSchedulePattern
{
    /**
     * Menghitung Shift apa yang berlaku pada tanggal tertentu
     * berdasarkan pola dan tanggal efektif.
     * * @param string|Carbon $targetDate Tanggal yang mau dicek
     * @return Shift|null Mengembalikan Object Shift atau Null (jika Libur/Error)
     */
    public function getShiftOnDate($targetDate)
    {
        $effectiveDate = Carbon::parse($this->effective_date);
        $date = Carbon::parse($targetDate);

        // 1. Validasi: Tanggal target tidak boleh sebelum tanggal efektif
        if ($date->lt($effectiveDate)) {
            return null; // Jadwal belum berlaku
        }

        // 2. Pastikan Pattern ada
        $pattern = $this->schedulePattern;
        if (!$pattern) {
            return null;
        }

        // 3. Ambil Detail Pattern (Sebaiknya di-eager load sebelumnya biar hemat query)
        $details = $pattern->details;
        $patternLength = $details->count();

        if ($patternLength === 0) {
            return null;
        }

        // RUMUS MODULO (INTI LOGIKA)
        // Hitung selisih hari dari tgl efektif

        $startIndex = $this->pattern_start_day_index ?? 1;

        // Hitung selisih hari
        $daysDiff = $effectiveDate->diffInDays($date);

        // Rumus Modulo dengan Offset
        // Penjelasan:
        // ($startIndex - 1) -> Kita ubah ke 0-based index dulu buat hitungan
        // + $daysDiff       -> Kita tambah hari yang berlalu
        // % $patternLength  -> Kita modulo dengan panjang pola
        // + 1               -> Kita kembalikan ke 1-based index untuk query DB
        $daySequence = (($daysDiff + ($startIndex - 1)) % $patternLength) + 1;

        // 4. Cari detail shift
        $detail = $details->firstWhere('day_index', $daySequence);

        return $detail ? $detail->shift : null;
    }
}
