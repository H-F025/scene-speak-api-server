<?php

namespace App\Providers;

use App\Services\Speech\AnswerEvaluatorInterface;
use App\Services\Speech\SimilarityAnswerEvaluator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // フェーズ1は文字列類似度で判定する。LLM 判定へ移行する際はここの実装クラスのみ差し替える
        $this->app->bind(AnswerEvaluatorInterface::class, SimilarityAnswerEvaluator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
