<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 2026_08_01_234449_create_expected_expressions_table を本番デプロイ済みの後に
 * 書き換えて question_id / text / is_primary を追加したが、Laravelは
 * ファイル名でマイグレーション実行済みを判定するため本番には反映されなかった。
 * そのため不足カラムを追加するマイグレーションを別途用意する。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expected_expressions', function (Blueprint $table) {
            if (! Schema::hasColumn('expected_expressions', 'question_id')) {
                $table->foreignId('question_id')->after('id')->constrained()->cascadeOnDelete();
            }

            if (! Schema::hasColumn('expected_expressions', 'text')) {
                $table->string('text')->after('question_id');
            }

            if (! Schema::hasColumn('expected_expressions', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expected_expressions', function (Blueprint $table) {
            if (Schema::hasColumn('expected_expressions', 'is_primary')) {
                $table->dropColumn('is_primary');
            }

            if (Schema::hasColumn('expected_expressions', 'text')) {
                $table->dropColumn('text');
            }

            if (Schema::hasColumn('expected_expressions', 'question_id')) {
                $table->dropConstrainedForeignId('question_id');
            }
        });
    }
};
