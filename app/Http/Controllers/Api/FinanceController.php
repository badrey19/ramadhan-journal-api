<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MonthlyBudget;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    public function addSalary(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'month' => 'required|string', // Format: 2026-02
        ]);

        $userId = Auth::id();
        $amount = $request->amount;
        $month = $request->month;

        return DB::transaction(function () use ($userId, $amount, $month) {
            // 1. Hitung Alokasi 50/30/20
            $needs = $amount * 0.5;
            $wants = $amount * 0.3;
            $savings = $amount * 0.2;

            // 2. Update atau Buat Budget Bulanan
            // Kita pakai increment agar jika input gaji 2x sebulan, saldonya bertambah
            $budget = MonthlyBudget::updateOrCreate(
                ['user_id' => $userId, 'month' => $month],
                []
            );
            $budget->increment('salary_input', $amount);
            $budget->increment('needs_balance', $needs);
            $budget->increment('wants_balance', $wants);
            $budget->increment('savings_balance', $savings);

            // 3. Update Saldo Utama (Wallets)
            $wallet = Wallet::firstOrCreate(['user_id' => $userId]);
            $wallet->increment('total_main_balance', $amount);

            // 4. Catat Transaksi Masuk
            Transaction::create([
                'user_id' => $userId,
                'type' => 'income',
                'amount' => $amount,
                'category' => 'needs', // Default kategori income masuk ke record transaksi
                'description' => "Gaji bulan $month",
                'month' => $month
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gaji berhasil dialokasikan!',
                'data' => $budget->fresh()
            ]);
        });
    }

    public function addExpense(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'category' => 'required|in:needs,wants,savings',
            'description' => 'nullable|string',
            'month' => 'required|string',
        ]);

        $userId = Auth::id();
        $categoryField = $request->category . '_balance';

        return DB::transaction(function () use ($userId, $request, $categoryField) {
            $budget = MonthlyBudget::where('user_id', $userId)
                        ->where('month', $request->month)
                        ->first();

            if (!$budget || $budget->$categoryField < $request->amount) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Saldo kategori ' . $request->category . ' tidak cukup!'
                ], 400);
            }

            // 1. Potong Saldo Bulanan
            $budget->decrement($categoryField, $request->amount);

            // 2. Potong Saldo Utama
            $wallet = Wallet::where('user_id', $userId)->first();
            $wallet->decrement('total_main_balance', $request->amount);

            // 3. Catat Transaksi Keluar
            Transaction::create([
                'user_id' => $userId,
                'type' => 'expense',
                'amount' => $request->amount,
                'category' => $request->category,
                'description' => $request->description,
                'month' => $request->month
            ]);

            return response()->json(['success' => true, 'message' => 'Pengeluaran berhasil dicatat!']);
        });
    }

    public function getSummary(Request $request)
    {
        $userId = auth()->id();
        $month = $request->query('month', now()->format('Y-m'));

        // 1. Ambil Saldo Utama
        $wallet = Wallet::where('user_id', $userId)->first();
        $totalMainBalance = $wallet ? $wallet->total_main_balance : 0;

        // 2. Ambil Budget Bulanan
        $monthly = MonthlyBudget::where('user_id', $userId)
                    ->where('month', $month)
                    ->first();

        // 3. AMBIL RIWAYAT TRANSAKSI (Tambahkan ini)
        // Kita hanya ambil yang tipenya 'expense' agar sesuai dengan list pengeluaran
        $expenses = Transaction::where('user_id', $userId)
                    ->where('month', $month)
                    ->where('type', 'expense')
                    ->orderBy('created_at', 'desc')
                    ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'total_main_balance' => (float) $totalMainBalance,
                'monthly_details' => [
                    'salary_input' => $monthly ? (float) $monthly->salary_input : 0,
                    'needs_left' => $monthly ? (float) $monthly->needs_balance : 0,
                    'wants_left' => $monthly ? (float) $monthly->wants_balance : 0,
                    'savings_left' => $monthly ? (float) $monthly->savings_balance : 0,
                    'total_monthly_left' => $monthly ? (float) ($monthly->needs_balance + $monthly->wants_balance + $monthly->savings_balance) : 0
                ],
                // 4. KIRIM DATA EXPENSES KE FLUTTER
                'expenses' => $expenses->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'amount' => (float) $item->amount,
                        'category' => $item->category,
                        'description' => $item->description,
                        'created_at' => $item->created_at->toDateTimeString(), // Sesuaikan format tanggal
                    ];
                })
            ]
        ]);
    }
}