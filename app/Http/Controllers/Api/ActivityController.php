<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActivityController extends Controller
{
    // Ambil semua kegiatan user untuk hari ini
    // app/Http/Controllers/Api/ActivityController.php

    public function index(Request $request)
    {
        $date = $request->query('date', Carbon::today()->toDateString());
        
        $activities = Activity::where('user_id', $request->user()->id)
                            ->whereDate('for_date', $date)
                            ->get();

        $total = $activities->count();
        $completed = $activities->where('is_completed', true)->count();
        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        return response()->json([
            'success' => true,
            'data' => $activities,
            'summary' => [
                'total' => $total,
                'completed' => $completed,
                'percentage' => $percentage
            ]
        ]);
    }

    // Tambah kegiatan baru
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
            'for_date' => Carbon::today()->toDateString(),
            'is_completed' => false
        ]);

        return response()->json(['success' => true, 'data' => $activity]);
    }

    // Check/Uncheck kegiatan
    public function toggle(Request $request, $id)
    {
        $activity = Activity::where('user_id', $request->user()->id)->findOrFail($id);
        $activity->is_completed = !$activity->is_completed;
        $activity->save();

        return response()->json(['success' => true, 'data' => $activity]);
    }
}