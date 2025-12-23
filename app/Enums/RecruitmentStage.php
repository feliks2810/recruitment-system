<?php

namespace App\Enums;

enum RecruitmentStage: string
{
    case PSIKOTES = 'psikotes';
    case HC_INTERVIEW = 'hc_interview';
    case USER_INTERVIEW = 'user_interview';
    case BOD_INTERVIEW = 'bod_interview';
    case OFFERING = 'offering';
    case MCU = 'mcu';
    case HIRED = 'hired';
    case FAILED = 'failed';
}
