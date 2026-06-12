<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scan_histories', function (Blueprint $table) {
            $table->string('status')->default('pending')->index()->after('type');
            $table->string('verdict')->nullable()->index()->after('total_engines');
            $table->json('result_json')->nullable()->after('ai_explanation');
            $table->timestamp('expires_at')->nullable()->index()->after('result_json');

            $table->index(['type', 'input_value'], 'scan_histories_type_input_value_index');
        });
    }

    public function down(): void
    {
        Schema::table('scan_histories', function (Blueprint $table) {
            $table->dropIndex('scan_histories_type_input_value_index');
            $table->dropIndex(['status']);
            $table->dropIndex(['verdict']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['status', 'verdict', 'result_json', 'expires_at']);
        });
    }
};
