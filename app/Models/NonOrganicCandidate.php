<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $applicant_id
 * @property string $nama
 * @property string $alamat_email
 * @property string $vacancy_airsys
 * @property string $current_stage
 * @property string $overall_status
 * @property string|null $contract_type
 * @property string|null $company
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate whereAlamatEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate whereApplicantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate whereContractType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate whereCurrentStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate whereOverallStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NonOrganicCandidate whereVacancyAirsys($value)
 * @mixin \Eloquent
 */
class NonOrganicCandidate extends Model
{
    protected $fillable = [
        'applicant_id',
        'nama',
        'alamat_email',
        'vacancy_airsys',
        'current_stage',
        'overall_status',
        'contract_type',
        'company',
    ];
}