<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Tambahkan ini
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();
        // Berikan URL lengkap untuk avatar agar Flutter mudah menampilkan
        if ($user->avatar) {
            $user->avatar = asset('storage/' . $user->avatar);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil',
            'data'    => $user
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'username'     => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'address'      => 'nullable|string',
            'avatar'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Validasi gambar
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Ambil data input selain avatar
        $data = $request->only(['name', 'username', 'phone_number', 'address']);

        // Logika Upload Avatar
        if ($request->hasFile('avatar')) {
            // Hapus foto lama jika ada
            if ($user->avatar) {
                Storage::delete('public/' . $user->avatar);
            }

            // Simpan foto baru ke folder storage/app/public/avatars
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        // Tambahkan URL lengkap untuk respon
        if ($user->avatar) {
            $user->avatar = asset('storage/' . $user->avatar);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data'    => $user
        ]);
    }
}