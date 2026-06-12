<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_histories', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'url' atau 'file'
            $table->text('input_value'); // Menyimpan string URL atau nama file asli
            $table->string('file_hash')->nullable()->index(); // Untuk SHA-256 (hanya jika tipe file), diberi index biar query cepat
            $table->integer('malicious_count')->default(0); // Jumlah antivirus yang mendeteksi bahaya
            $table->integer('total_engines')->default(0); // Total antivirus yang nge-scan
            $table->text('ai_explanation')->nullable(); // Tempat menyimpan teks hasil generate dari Nemotron AI
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_histories');
    }
};