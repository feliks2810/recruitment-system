<?php

namespace App\Services;

use App\Models\Candidate;
use App\Enums\RecruitmentStage;

class CandidateStageService
{
    public function resolve(Candidate $candidate): RecruitmentStage
    {
        $application = $candidate->latestApplication;

        if (!$application) {
            return RecruitmentStage::PSIKOTES;
        }

        // ❌ Psikotes gagal
        if ($application->psikotest_result === 'GAGAL') {
            return RecruitmentStage::FAILED;
        }

        // ✅ Psikotes lulus
        if ($application->psikotest_result === 'LULUS') {

            if (!$application->hc_interview_result) {
                return RecruitmentStage::HC_INTERVIEW;
            }

            if ($application->hc_interview_result === 'GAGAL') {
                return RecruitmentStage::FAILED;
            }

            if (!$application->user_interview_result) {
                return RecruitmentStage::USER_INTERVIEW;
            }

            if ($application->user_interview_result === 'GAGAL') {
                return RecruitmentStage::FAILED;
            }

            if (!$application->bod_interview_result) {
                return RecruitmentStage::BOD_INTERVIEW;
            }

            if ($application->bod_interview_result === 'GAGAL') {
                return RecruitmentStage::FAILED;
            }

            if (!$application->offering_status) {
                return RecruitmentStage::OFFERING;
            }

            if ($application->offering_status === 'DITERIMA') {
                return RecruitmentStage::HIRED;
            }
        }

        return RecruitmentStage::PSIKOTES;
    }
}
