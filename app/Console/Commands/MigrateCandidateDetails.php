<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateCandidateDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-candidate-details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of candidate details...');

        \App\Models\Candidate::chunk(100, function ($candidates) {
            foreach ($candidates as $candidate) {
                // Migrate Education data
                $educationData = [];
                if (!empty($candidate->jenjang_pendidikan)) $educationData['level'] = $candidate->jenjang_pendidikan;
                if (!empty($candidate->perguruan_tinggi)) $educationData['institution'] = $candidate->perguruan_tinggi;
                if (!empty($candidate->jurusan)) $educationData['major'] = $candidate->jurusan;
                if (!empty($candidate->ipk)) $educationData['gpa'] = $candidate->ipk;

                if (!empty($educationData)) {
                    $candidate->educations()->updateOrCreate(
                        ['candidate_id' => $candidate->id, 'level' => $educationData['level'] ?? null], // Unique key for education
                        $educationData
                    );
                }

                // Migrate Profile data
                $profileData = [
                    'applicant_id' => $candidate->applicant_id,
                    'alamat' => $candidate->address, // Assuming 'address' is the correct field in Candidate for 'alamat'
                    'tanggal_lahir' => $candidate->birth_date,
                    'jk' => $candidate->jk,
                    'phone' => $candidate->phone,
                    'email' => $candidate->email,
                ];

                // Filter out null/empty values to avoid overwriting with empty data
                $profileData = array_filter($profileData, function($value) { return !is_null($value) && $value !== ''; });

                if (!empty($profileData)) {
                    $candidate->profile()->updateOrCreate(
                        ['candidate_id' => $candidate->id], // Profile is usually one-to-one
                        $profileData
                    );
                }
                $this->info('Processed candidate: ' . $candidate->nama);
            }
        });

        $this->info('Migration complete!');
    }
}
