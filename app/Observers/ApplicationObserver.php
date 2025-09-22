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
        // Create the initial 'cv_review' stage for the new application
        $application->stages()->create([
            'stage_name' => 'cv_review',
            'status' => 'PROSES',
            'scheduled_date' => now(),
            'conducted_by_user_id' => Auth::id(), // Use user ID, will be null if no user
        ]);
    }

    /**
     * Handle the Application "updated" event.
     */
    public function updated(Application $application): void
    {
        $failedStatuses = ['TIDAK LULUS', 'DITOLAK', 'TIDAK DIHIRING', 'FAIL'];

        // Check if the overall_status was changed to a failed status
        if ($application->isDirty('overall_status') && in_array($application->overall_status, $failedStatuses)) {
            // Delete all future scheduled stages for this application
            $application->stages()->where('scheduled_date', '>=', now()->startOfDay())->delete();
        }
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
