<?php

namespace App\Observers;

use App\Models\Application;
use Illuminate\Support\Facades\Auth;

class ApplicationObserver
{
    /**
     * Handle the Application "created" event.
     */
    public function created(Application $application): void
    {
        // Create the initial 'psikotes' stage for the new application, as 'cv_review' is now bypassed.
        $application->stages()->create([
            'stage_name' => 'psikotes',
            'status' => 'PROSES',
            'scheduled_date' => now(),
            'conducted_by' => Auth::user()?->name ?? 'System',
        ]);
    }

    /**
     * Handle the Application "updated" event.
     */
    public function updated(Application $application): void
    {
        //
    }

    /**
     * Handle the Application "deleted" event.
     */
    public function deleted(Application $application): void
    {
        //
    }

    /**
     * Handle the Application "restored" event.
     */
    public function restored(Application $application): void
    {
        //
    }

    /**
     * Handle the Application "force deleted" event.
     */
    public function forceDeleted(Application $application): void
    {
        //
    }
}
