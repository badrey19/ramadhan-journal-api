<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title'); // Contoh: Membaca Sholawat
            $table->integer('target')->default(1); // Contoh: 1000 (kali)
            $table->string('unit')->default('kali'); // kali, juz, halaman
            $table->boolean('is_completed')->default(false);
            $table->date('for_date'); // Penting: Agar list bisa reset tiap hari
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
