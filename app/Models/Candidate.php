<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $no
 * @property string $vacancy
 * @property string|null $position
 * @property string|null $on_process_by
 * @property string|null $position_on_process
 * @property string|null $applicant_id
 * @property string $nama
 * @property string $source
 * @property string|null $jk
 * @property string|null $tanggal_lahir
 * @property string|null $alamat
 * @property string|null $email
 * @property string|null $jenjang_pendidikan
 * @property string|null $perguruan_tinggi
 * @property string|null $jurusan
 * @property string|null $ipk
 * @property string|null $cv
 * @property string|null $flk
 * @property string|null $psikotest_date
 * @property string|null $psikotes_result
 * @property string|null $psikotes_notes
 * @property string|null $hc_intv_date
 * @property string|null $hc_intv_status
 * @property string|null $hc_intv_notes
 * @property string|null $user_intv_date
 * @property string|null $user_intv_status
 * @property string|null $itv_user_note
 * @property string|null $bod_intv_date
 * @property string|null $bod_intv_status
 * @property string|null $bod_intv_note
 * @property string|null $offering_letter_date
 * @property string|null $offering_letter_status
 * @property string|null $offering_letter_notes
 * @property string|null $mcu_date
 * @property string|null $mcu_status
 * @property string|null $mcu_note
 * @property string|null $hiring_date
 * @property string|null $hiring_status
 * @property string|null $hiring_note
 * @property string $current_stage
 * @property string $overall_status
 * @property string $airsys_internal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $hiringStatus
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereAirsysInternal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereAlamat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereApplicantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereBodIntvDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereBodIntvNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereBodIntvStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCurrentStage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereCv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereFlk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereHcIntvDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereHcIntvNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereHcIntvStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereHiringDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereHiringNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereHiringStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereIpk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereItvUserNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereJenjangPendidikan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereJk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereJurusan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereMcuDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereMcuNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereMcuStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereOfferingLetterDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereOfferingLetterNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereOfferingLetterStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereOnProcessBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereOverallStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate wherePerguruanTinggi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate wherePositionOnProcess($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate wherePsikotesNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate wherePsikotesResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate wherePsikotestDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereTanggalLahir($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereUserIntvDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereUserIntvStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Candidate whereVacancy($value)
 * @mixin \Eloquent
 */
class Candidate extends Model
{
    protected $fillable = [
        'no', 'vacancy_airsys', 'internal_position', 'on_process_by', 'applicant_id', 'nama',
        'source', 'jk', 'tanggal_lahir', 'alamat_email', 'jenjang_pendidikan', 'perguruan_tinggi',
        'jurusan', 'ipk', 'cv', 'flk', 'psikotest_date', 'psikotes_result', 'psikotes_notes',
        'hc_intv_date', 'hc_intv_status', 'hc_intv_notes', 'user_intv_date', 'user_intv_status',
        'itv_user_note', 'bod_gm_intv_date', 'bod_intv_status', 'bod_intv_note',
        'offering_letter_date', 'offering_letter_status', 'offering_letter_notes', 'mcu_date',
        'mcu_status', 'mcu_note', 'hiring_date', 'hiring_status', 'hiring_note', 'current_stage',
        'overall_status', 'airsys_internal',
    ];
}