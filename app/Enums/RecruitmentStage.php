<?php

namespace App\Enums;

enum RecruitmentStage: string
{
    case PSIKOTES = 'psikotes';
    case HC_INTERVIEW = 'hc_interview';
    case USER_INTERVIEW = 'user_interview';
    case INTERVIEW_BOD = 'interview_bod';
    case OFFERING_LETTER = 'offering_letter';
    case MCU = 'mcu';
    case HIRING = 'hiring';
}
