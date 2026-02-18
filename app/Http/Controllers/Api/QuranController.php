<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuranKhatam;
use App\Models\QuranProgress;
use Illuminate\Http\Request;

class QuranController extends Controller
{
    //

    public function getStatus(Request $request) {
        $user = $request->user();
        
        // Juz berapa saja yang sudah dicentang?
        $completedJuz = QuranProgress::where('user_id', $user->id)
                        ->pluck('juz_number')
                        ->toArray();

        // Sudah berapa kali khatam?
        $totalKhatam = QuranKhatam::where('user_id', $user->id)->count();
        
        // Riwayat khatam
        $history = QuranKhatam::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'current_khatam_index' => $totalKhatam + 1,
            'completed_juz' => $completedJuz,
            'history' => $history
        ]);
    }

    public function toggleJuz(Request $request, $juz) {
        $user = $request->user();
        
        $exists = QuranProgress::where('user_id', $user->id)->where('juz_number', $juz)->first();

        if ($exists) {
            $exists->delete();
            $status = false;
        } else {
            QuranProgress::create(['user_id' => $user->id, 'juz_number' => $juz]);
            $status = true;
        }

        return response()->json(['success' => true, 'is_completed' => $status]);
    }

    public function completeKhatam(Request $request) {
        $user = $request->user();
        
        // Cek apakah benar sudah 30 juz
        $count = QuranProgress::where('user_id', $user->id)->count();
        if ($count < 30) {
            return response()->json(['success' => false, 'message' => 'Belum semua juz selesai!'], 400);
        }

        // Hitung ini khatam yang keberapa
        $nextKhatamNumber = QuranKhatam::where('user_id', $user->id)->count() + 1;

        // 1. Simpan ke history
        QuranKhatam::create([
            'user_id' => $user->id,
            'khatam_number' => $nextKhatamNumber,
            'completed_at' => now()->toDateString()
        ]);

        // 2. Reset progres juz (hapus juz 1-30 milik user ini)
        QuranProgress::where('user_id', $user->id)->delete();

        return response()->json(['success' => true, 'message' => 'Selamat! Anda telah khatam.']);
    }
}
