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
    public function toggle(Request $request, $id)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $activity = Activity::where('user_id', $user->id)->findOrFail($id);

        // Gunakan updateOrCreate dengan nilai awal jika baru dibuat
        $log = ActivityLog::firstOrCreate(
            [
                'user_id' => $user->id,
                'activity_id' => $id,
                'log_date' => $today
            ],
            [
                'is_completed' => false // Default awal jika baru dibuat hari ini
            ]
        );
        
        // Toggle nilainya
        $log->is_completed = !$log->is_completed;
        $log->save();

        return response()->json([
            'success' => true, 
            'is_completed' => (bool)$log->is_completed
        ]);
    }

    /**
     * HISTORY: Mengambil riwayat amalan berdasarkan tanggal
     */
    public function history(Request $request)
    {
        $user = $request->user();

        // 1. Ambil semua master amalan user
        $masterActivities = Activity::where('user_id', $user->id)->get();

        // 2. Ambil log amalan dan kelompokkan berdasarkan tanggal
        $historyLogs = ActivityLog::where('user_id', $user->id)
            ->orderBy('log_date', 'desc')
            ->get()
            ->groupBy('log_date');

        $result = [];

        foreach ($historyLogs as $date => $logs) {
            // Buat map activity_id dari log agar mudah dicek
            $logMap = $logs->keyBy('activity_id');

            $details = $masterActivities->map(function($activity) use ($logMap) {
                $log = $logMap->get($activity->id);
                return [
                    'title' => $activity->title,
                    // Jika ada log dan is_completed true, maka true. Selain itu false.
                    'is_completed' => $log ? (bool)$log->is_completed : false,
                ];
            });

            $result[] = [
                'date' => $date,
                'completed_count' => $details->where('is_completed', true)->count(),
                'total_count' => $details->count(), // Tambahan info total amalan
                'details' => $details
            ];
        }

        return response()->json(['success' => true, 'data' => $result]);
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