<?php
// database/seeders/CandidateSeeder.php

namespace Database\Seeders;

use App\Models\Candidate;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CandidateSeeder extends Seeder
{
    public function run()
    {
        $candidates = [
            [
                'no' => '001',
                'applicant_id' => 'APP001',
                'nama' => 'Ahmad Rizki Pratama',
                'source' => 'Job Portal',
                'jk' => 'L',
                'tanggal_lahir' => '1995-05-15',
                'alamat_email' => 'ahmad.rizki@email.com',
                'jenjang_pendidikan' => 'S1',
                'perguruan_tinggi' => 'Universitas Indonesia',
                'jurusan' => 'Teknik Informatika',
                'ipk' => 3.75,
                'cv' => 'CV_Ahmad_Rizki.pdf',
                'flk' => 'FLK_Ahmad_Rizki.pdf',
                'airsys_internal' => 'No',
                'is_suspected_duplicate' => false,
            ],
            [
                'no' => '002',
                'applicant_id' => 'APP002',
                'nama' => 'Sari Dewi Lestari',
                'source' => 'Referral',
                'jk' => 'P',
                'tanggal_lahir' => '1993-08-22',
                'alamat_email' => 'sari.dewi@email.com',
                'jenjang_pendidikan' => 'S1',
                'perguruan_tinggi' => 'Universitas Gadjah Mada',
                'jurusan' => 'Manajemen Pemasaran',
                'ipk' => 3.65,
                'cv' => 'CV_Sari_Dewi.pdf',
                'flk' => 'FLK_Sari_Dewi.pdf',
                'airsys_internal' => 'Yes',
                'is_suspected_duplicate' => false,
            ],
            [
                'no' => '003',
                'applicant_id' => 'APP003',
                'nama' => 'Budi Santoso',
                'source' => 'LinkedIn',
                'jk' => 'L',
                'tanggal_lahir' => '1990-12-10',
                'alamat_email' => 'budi.santoso@email.com',
                'jenjang_pendidikan' => 'S1',
                'perguruan_tinggi' => 'Institut Teknologi Bandung',
                'jurusan' => 'Teknik Mesin',
                'ipk' => 3.85,
                'cv' => 'CV_Budi_Santoso.pdf',
                'flk' => 'FLK_Budi_Santoso.pdf',
                'airsys_internal' => 'No',
                'is_suspected_duplicate' => false,
            ],
        ];

        foreach ($candidates as $candidateData) {
            Candidate::create($candidateData);
        }
    }
}