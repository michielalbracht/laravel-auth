<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('magic_link_tokens') || Schema::hasColumn('magic_link_tokens', 'code')) {
            return;
        }

        Schema::table('magic_link_tokens', function (Blueprint $table) {
            $table->string('code')->nullable()->after('token');
            $table->unsignedTinyInteger('attempts')->default(0)->after('code');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('magic_link_tokens')) {
            return;
        }

        Schema::table('magic_link_tokens', function (Blueprint $table) {
            $table->dropColumn(['code', 'attempts']);
        });
    }
};
