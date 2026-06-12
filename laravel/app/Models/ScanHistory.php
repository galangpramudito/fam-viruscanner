<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanHistory extends Model
{
    use HasFactory;

    // 💡 Daftarkan semua kolom tabel di bawah ini agar diizinkan masuk ke database
    protected $fillable = [
        'type',
        'input_value',
        'file_hash',
        'malicious_count',
        'total_engines',
        'ai_explanation',
    ];
}