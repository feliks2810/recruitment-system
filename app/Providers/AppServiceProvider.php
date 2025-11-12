<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\Vacancy;
use App\Observers\ApplicationObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Application::observe(ApplicationObserver::class);
        
        // Share pending proposals count with sidebar view
        View::composer('layouts.sidebar', function ($view) {
            $pendingProposalsCount = Vacancy::where('proposal_status', 'pending')->count();
            $view->with('pendingProposalsCount', $pendingProposalsCount);
        });
    }
}
