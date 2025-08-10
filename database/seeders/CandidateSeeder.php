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
                'vacancy' => 'Software Engineer',
                'airsys_internal' => 'No',
                // removed legacy field
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
                'psikotest_date' => '2024-01-18',
                'psikotes_result' => 'LULUS',
                'psikotes_notes' => 'Hasil psikotes baik, skor 85/100. Kemampuan analitis tinggi.',
                'hc_interview_date' => '2024-01-20',
                'hc_interview_status' => 'DISARANKAN',
                'hc_interview_notes' => 'Komunikasi baik, motivasi tinggi, sesuai culture fit.',
                'current_stage' => 'User Interview',
                'overall_status' => 'DALAM PROSES',
            ],
            [
                'no' => '002',
                'vacancy' => 'Marketing Specialist',
                'airsys_internal' => 'Yes',
                // removed legacy field
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
                'psikotest_date' => '2024-01-16',
                'psikotes_result' => 'LULUS',
                'psikotes_notes' => 'Skor psikotes 88/100. Kepribadian ekstrovert, cocok untuk marketing.',
                'hc_interview_date' => '2024-01-18',
                'hc_interview_status' => 'DISARANKAN',
                'hc_interview_notes' => 'Pengalaman marketing yang baik, komunikasi excellent.',
                'user_interview_date' => '2024-01-20',
                'user_interview_status' => 'DISARANKAN',
                'user_interview_notes' => 'Memahami strategi marketing digital, portfolio bagus.',
                'bodgm_interview_date' => '2024-01-24',
                'bod_interview_status' => 'DISARANKAN',
                'bod_interview_notes' => 'Visi yang sejalan dengan perusahaan, leadership potential.',
                'offering_letter_date' => '2024-01-26',
                'offering_letter_status' => 'SENT',
                'offering_letter_notes' => 'Offering letter dikirim, menunggu konfirmasi kandidat.',
                'current_stage' => 'Offering Letter',
                'overall_status' => 'DALAM PROSES',
            ],
            [
                'no' => '003',
                'vacancy' => 'Mechanical Engineer',
                'airsys_internal' => 'No',
                // removed legacy field
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
                'psikotest_date' => '2024-01-10',
                'psikotes_result' => 'LULUS',
                'psikotes_notes' => 'Skor psikotes 90/100. Kemampuan problem solving excellent.',
                'hc_interview_date' => '2024-01-12',
                'hc_interview_status' => 'DISARANKAN',
                'hc_interview_notes' => 'Pengalaman engineering 5 tahun, attitude positif.',
                'user_interview_date' => '2024-01-15',
                'user_interview_status' => 'DISARANKAN',
                'user_interview_notes' => 'Technical skill sangat baik, pengalaman proyek besar.',
                'bodgm_interview_date' => '2024-01-18',
                'bod_interview_status' => 'DISARANKAN',
                'bod_interview_notes' => 'Kandidat terbaik, siap untuk posisi senior engineer.',
                'offering_letter_date' => '2024-01-20',
                'offering_letter_status' => 'DITERIMA',
                'offering_letter_notes' => 'Offering letter diterima, negosiasi salary selesai.',
                'mcu_date' => '2024-01-25',
                'mcu_status' => 'LULUS',
                'mcu_notes' => 'Hasil MCU baik, tidak ada masalah kesehatan.',
                'hiring_date' => '2024-01-30',
                'hiring_status' => 'HIRED',
                'hiring_notes' => 'Onboarding dimulai 1 Februari 2024.',
                'current_stage' => 'Hired',
                'overall_status' => 'LULUS',
            ],
        ];

        foreach ($candidates as $candidateData) {
            Candidate::create($candidateData);
        }
    }
}