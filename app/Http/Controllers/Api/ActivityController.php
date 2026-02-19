<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityLog; // Pastikan model ini ada
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActivityController extends Controller
{
    /**
     * TAMPILAN UTAMA: List amalan hari ini (Otomatis Uncheck jika hari baru)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        // Gunakan timezone yang konsisten
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        
        // 1. Ambil semua master amalan
        $masterActivities = Activity::where('user_id', $user->id)->get();

        // 2. Ambil SEMUA log hari ini sekaligus (Bukan satu-satu di dalam loop)
        $logsToday = ActivityLog::where('user_id', $user->id)
                    ->whereDate('log_date', $today)
                    ->get()
                    ->keyBy('activity_id'); // Kita index berdasarkan activity_id agar mudah dicari

        // 3. Gabungkan data
        $data = $masterActivities->map(function ($activity) use ($logsToday) {
            // Cari apakah ada log untuk ID ini di koleksi logsToday
            $log = $logsToday->get($activity->id);

            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'target' => $activity->target,
                'unit' => $activity->unit,
                'is_completed' => $log ? (bool)$log->is_completed : false,
            ];
        });

        $total = $data->count();
        $completed = $data->where('is_completed', true)->count();

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0
            ]
        ]);
    }

    /**
     * TOGGLE: Klik centang (Data masuk ke tabel logs)
     */
    

    /**
     * HISTORY: Mengambil riwayat amalan berdasarkan tanggal
     */
    // app/Http/Controllers/ActivityController.php

public function history(Request $request) {
    $user = $request->user();
    
    // Ambil semua master amalan (agar amalan yg tidak dikerjakan tetap muncul)
    $masterActivities = Activity::where('user_id', $user->id)->get();

    // Ambil log, kelompokkan berdasarkan tanggal
    $logsByDate = ActivityLog::where('user_id', $user->id)
        ->orderBy('log_date', 'desc')
        ->get()
        ->groupBy('log_date');

    $historyData = [];

    foreach ($logsByDate as $date => $logs) {
        $logMap = $logs->keyBy('activity_id');

        // Map detail amalan untuk tanggal tersebut
        $details = $masterActivities->map(function($activity) use ($logMap) {
            $log = $logMap->get($activity->id);
            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'is_completed' => $log ? (bool)$log->is_completed : false,
            ];
        });

        $historyData[] = [
            'date' => $date,
            'completed_count' => $details->where('is_completed', true)->count(),
            'details' => $details->values()
        ];
    }

    return response()->json(['success' => true, 'data' => $historyData]);
}

public function toggle(Request $request, $id) {
    $user = $request->user();
    $today = date('Y-m-d');

    // Cari log untuk hari ini
    $log = ActivityLog::where('user_id', $user->id)
        ->where('activity_id', $id)
        ->where('log_date', $today)
        ->first();

    if ($log) {
        $log->update(['is_completed' => !$log->is_completed]);
    } else {
        ActivityLog::create([
            'user_id' => $user->id,
            'activity_id' => $id,
            'log_date' => $today,
            'is_completed' => true
        ]);
    }

    return response()->json(['success' => true]);
}

    // --- Sisanya (store, update, destroy) tetap mengelola tabel Activity (Master) ---

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'target' => 'required|integer',
            'unit' => 'required|string',
        ]);

        $activity = Activity::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'target' => $request->target,
            'unit' => $request->unit,
        ]);

        return response()->json(['success' => true, 'data' => $activity]);
    }

    public function update(Request $request, $id)
    {
        $activity = Activity::where('user_id', $request->user()->id)->findOrFail($id);
        $activity->update($request->only(['title', 'target', 'unit']));

        return response()->json(['success' => true, 'data' => $activity]);
    }

    public function destroy(Request $request, $id)
    {
        $activity = Activity::where('user_id', $request->user()->id)->findOrFail($id);
        $activity->delete();
        return response()->json(['success' => true, 'message' => 'Master amalan dihapus']);
    }
}