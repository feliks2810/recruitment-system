<?php

namespace App\Observers;

use App\Models\Candidate;
use App\Models\Profile;
use App\Models\Education;

class CandidateObserver
{
    /**
     * Handle the Candidate "created" event.
     */
    public function created(Candidate $candidate): void
    {
        // Create a default Profile, syncing data from the candidate
        $candidate->profile()->create([
            'email' => $candidate->alamat_email,
            'tanggal_lahir' => $candidate->tanggal_lahir,
            'jk' => $candidate->jk,
            'applicant_id' => $candidate->applicant_id
        ]);

        // Create a default Education record if education data exists on the candidate model
        if ($candidate->jenjang_pendidikan || $candidate->perguruan_tinggi) {
            $candidate->educations()->create([
                'level' => $candidate->jenjang_pendidikan,
                'institution' => $candidate->perguruan_tinggi,
                'major' => $candidate->jurusan,
                'gpa' => $candidate->ipk,
            ]);
        }
    }

    /**
     * Handle the Candidate "updated" event.
     */
    public function updated(Candidate $candidate): void
    {
        //
    }

    /**
     * Handle the Candidate "deleted" event.
     */
    public function deleted(Candidate $candidate): void
    {
        //
    }

    /**
     * Handle the Candidate "restored" event.
     */
    public function restored(Candidate $candidate): void
    {
        //
    }

    /**
     * Handle the Candidate "force deleted" event.
     */
    public function forceDeleted(Candidate $candidate): void
    {
        //
    }
}
