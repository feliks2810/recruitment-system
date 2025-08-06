<?php

namespace App\Imports;

use App\Models\Candidate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CandidatesImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsEmptyRows
{
    protected $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function model(array $row)
    {
        try {
            // Logika untuk non-organik
            if ($this->type == 'non-organic') {
                $nama = $row['nama_posisi'] ?? 'Kandidat Non-Organik ' . Str::random(6);
                $email = $row['alamat_email'] ?? 'no-email-' . Str::random(8) . '@example.com';
                $vacancyAirsys = $row['nama_posisi'] ? ($row['dept'] . ' - ' . $row['nama_posisi']) : ($row['dept'] ?? 'Tidak Diketahui');
                $source = $row['sourcing_rekrutmen_internal_eksternal'] ?? 'Eksternal';
                $quantity = $row['quantity_target'] ?? 1;

                $candidates = [];
                for ($i = 0; $i < $quantity; $i++) {
                    $applicantId = 'CAND-' . Str::random(6);
                    if (Candidate::where('alamat_email', $email)->exists() || Candidate::where('applicant_id', $applicantId)->exists()) {
                        Log::warning('Duplikat email atau applicant_id ditemukan, melewati baris', ['email' => $email, 'applicant_id' => $applicantId]);
                        continue;
                    }

                    $candidates[] = new Candidate([
                        'no' => $row['no'] ?? Candidate::count() + 1,
                        'vacancy_airsys' => $vacancyAirsys,
                        'internal_position' => $row['nama_posisi'] ?? null,
                        'on_process_by' => null,
                        'applicant_id' => $applicantId,
                        'nama' => $nama . ($quantity > 1 ? ' ' . ($i + 1) : ''),
                        'source' => $source,
                        'jk' => $row['jk'] ?? null,
                        'tanggal_lahir' => null,
                        'alamat_email' => $email,
                        'jenjang_pendidikan' => 'Tidak Diketahui',
                        'perguruan_tinggi' => 'Tidak Diketahui',
                        'jurusan' => 'Tidak Diketahui',
                        'ipk' => null,
                        'cv' => null,
                        'flk' => null,
                        'psikotest_date' => null,
                        'psikotes_result' => null,
                        'psikotes_notes' => null,
                        'hc_intv_date' => null,
                        'hc_intv_status' => null,
                        'hc_intv_notes' => null,
                        'user_intv_date' => null,
                        'user_intv_status' => null,
                        'itv_user_note' => null,
                        'bod_gm_intv_date' => null,
                        'bod_intv_status' => null,
                        'bod_intv_note' => null,
                        'offering_letter_date' => null,
                        'offering_letter_status' => null,
                        'offering_letter_notes' => null,
                        'mcu_date' => null,
                        'mcu_status' => null,
                        'mcu_note' => null,
                        'hiring_date' => $this->transformDate($row['hiring_date'] ?? $row['join_date'] ?? null),
                        'hiring_status' => null,
                        'hiring_note' => $row['catatan'] ?? null,
                        'current_stage' => 'CV Review',
                        'overall_status' => 'DALAM PROSES',
                        'airsys_internal' => 'No',
                    ]);
                }
                return $candidates;
            }

            // Logika untuk organik
            $nama = $row['nama'] ?? null;
            $email = $row['alamat_email'] ?? $row['email'] ?? null;
            if (!$nama || !$email) {
                Log::warning('Kolom wajib untuk kandidat organik tidak lengkap', ['row' => $row]);
                return null;
            }
            if (Candidate::where('alamat_email', $email)->exists()) {
                Log::info('Melewati email duplikat', ['email' => $email, 'row' => $row]);
                return null;
            }

            $applicantId = $row['applicant_id'] ?? 'CAND-' . Str::random(6);
            if (Candidate::where('applicant_id', $applicantId)->exists()) {
                Log::info('Duplikat applicant_id ditemukan, membuat ID baru', ['applicant_id' => $applicantId]);
                $applicantId = 'CAND-' . Str::random(6);
            }

            $psikotesResult = $this->transformPsikotesResult($row['psikotes_result'] ?? null);
            $hiringStatus = $this->transformStatus($row['hiring_status'] ?? 'TIDAK DIHIRING');

            return new Candidate([
                'no' => $row['no'] ?? Candidate::count() + 1,
                'vacancy_airsys' => $row['vacancy'] ?? $row['vacancy_airsys'] ?? null,
                'internal_position' => $row['internal_position'] ?? $row['position'] ?? null,
                'on_process_by' => $row['on_process_by'] ?? null,
                'applicant_id' => $applicantId,
                'nama' => $nama,
                'source' => $row['source'] ?? null,
                'jk' => $row['jk'] ?? null,
                'tanggal_lahir' => $this->transformDate($row['tanggal_lahir'] ?? null),
                'alamat_email' => $email,
                'jenjang_pendidikan' => $row['jenjang_pendidikan'] ?? 'Tidak Diketahui',
                'perguruan_tinggi' => $row['perguruan_tinggi'] ?? 'Tidak Diketahui',
                'jurusan' => $row['jurusan'] ?? 'Tidak Diketahui',
                'ipk' => $row['ipk'] ?? null,
                'cv' => null, // File CV/FLK tidak diimpor dari Excel, bisa ditambahkan jika ada
                'flk' => null,
                'psikotest_date' => $this->transformDate($row['psikotest_date'] ?? null),
                'psikotes_result' => $psikotesResult,
                'psikotes_notes' => $row['psikotes_notes'] ?? null,
                'hc_intv_date' => $this->transformDate($row['hc_intv_date'] ?? null),
                'hc_intv_status' => $this->transformStatus($row['hc_intv_status'] ?? null),
                'hc_intv_notes' => $row['hc_intv_notes'] ?? null,
                'user_intv_date' => $this->transformDate($row['user_intv_date'] ?? null),
                'user_intv_status' => $this->transformStatus($row['user_intv_status'] ?? null),
                'itv_user_note' => $row['itv_user_note'] ?? null,
                'bod_gm_intv_date' => $this->transformDate($row['bodgm_intv_date'] ?? $row['bod_intv_date'] ?? null),
                'bod_intv_status' => $this->transformStatus($row['bod_intv_status'] ?? null),
                'bod_intv_note' => $row['bod_intv_note'] ?? null,
                'offering_letter_date' => $this->transformDate($row['offering_letter_date'] ?? null),
                'offering_letter_status' => $this->transformStatus($row['offering_letter_status'] ?? null),
                'offering_letter_notes' => $row['offering_letter_notes'] ?? null,
                'mcu_date' => $this->transformDate($row['mcu_date'] ?? null),
                'mcu_status' => $this->transformStatus($row['mcu_status'] ?? null),
                'mcu_note' => $row['mcu_note'] ?? null,
                'hiring_date' => $this->transformDate($row['hiring_date'] ?? null),
                'hiring_status' => $hiringStatus,
                'hiring_note' => $row['hiring_note'] ?? null,
                'current_stage' => $row['current_stage'] ?? 'CV Review',
                'overall_status' => $row['overall_status'] ?? 'DALAM PROSES',
                'airsys_internal' => 'Yes',
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan kandidat', [
                'row' => $row,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    public function rules(): array
    {
        if ($this->type == 'non-organic') {
            return [
                'nama_posisi' => 'required|string',
                'dept' => 'required|string',
                'sourcing_rekrutmen_internal_eksternal' => 'nullable|string',
                'company' => 'nullable|string',
                'form_a1b1_submitted_date' => 'nullable|date',
                'waktu_pemenuhan_target' => 'nullable|date',
                'quantity_target' => 'nullable|integer|min:1',
                'jk' => 'nullable|string|max:10',
                'ipk' => 'nullable|numeric|min:0|max:4',
                'jenjang_pendidikan' => 'nullable|string',
                'perguruan_tinggi' => 'nullable|string',
                'jurusan' => 'nullable|string',
                'catatan' => 'nullable|string',
                'hiring_date' => 'nullable|date', // Mengganti join_date dengan hiring_date
            ];
        }

        return [
            'nama' => 'required|string',
            'vacancy' => 'required|string',
            'vacancy_airsys' => 'required|string',
            'email' => 'nullable|email|unique:candidates,alamat_email',
            'alamat_email' => 'nullable|email|unique:candidates,alamat_email',
            'jk' => 'nullable|string|max:10',
            'tanggal_lahir' => 'nullable|date',
            'psikotest_date' => 'nullable|date',
            'hc_intv_date' => 'nullable|date',
            'user_intv_date' => 'nullable|date',
            'bodgm_intv_date' => 'nullable|date',
            'offering_letter_date' => 'nullable|date',
            'mcu_date' => 'nullable|date',
            'hiring_date' => 'nullable|date',
            'ipk' => 'nullable|numeric|min:0|max:4',
            'cv' => 'nullable|string',
            'flk' => 'nullable|string',
            'psikotes_result' => 'nullable|in:LULUS,TIDAK LULUS,PASS,DIPERTIMBANGKAN',
            'hc_intv_status' => 'nullable|in:DISARANKAN,TIDAK DISARANKAN,DIPERTIMBANGKAN,CANCEL',
            'user_intv_status' => 'nullable|in:DISARANKAN,TIDAK DISARANKAN,DIPERTIMBANGKAN,CANCEL',
            'bod_intv_status' => 'nullable|in:DISARANKAN,TIDAK DISARANKAN,DIPERTIMBANGKAN,CANCEL',
            'offering_letter_status' => 'nullable|in:DITERIMA,DITOLAK',
            'mcu_status' => 'nullable|in:LULUS,TIDAK LULUS',
            'hiring_status' => 'nullable|in:DIHIRING,TIDAK DIHIRING',
            'jenjang_pendidikan' => 'nullable|string',
            'perguruan_tinggi' => 'nullable|string',
            'jurusan' => 'nullable|string',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    private function transformDate($value)
    {
        if (!$value) return null;

        try {
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }
            $value = str_replace('25', '2025', $value); // Sesuaikan dengan tahun saat ini
            return \Carbon\Carbon::createFromFormat('d-M-Y', $value)->format('Y-m-d') ??
                   \Carbon\Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning('Gagal memparsing tanggal', [
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function transformPsikotesResult($value)
    {
        if (!$value) return null;
        $resultMap = [
            'PASS' => 'LULUS',
            'LULUS' => 'LULUS',
            'TIDAK LULUS' => 'TIDAK LULUS',
            'DIPERTIMBANGKAN' => 'DIPERTIMBANGKAN',
        ];
        return $resultMap[$value] ?? $value;
    }

    private function transformStatus($value)
    {
        if (!$value) return null;
        $statusMap = [
            'DISARANKAN' => 'DISARANKAN',
            'TIDAK DISARANKAN' => 'TIDAK DISARANKAN',
            'DIPERTIMBANGKAN' => 'DIPERTIMBANGKAN',
            'CANCEL' => 'CANCEL',
            'DITERIMA' => 'DITERIMA',
            'DITOLAK' => 'DITOLAK',
            'LULUS' => 'LULUS',
            'TIDAK LULUS' => 'TIDAK LULUS',
            'DIHIRING' => 'DIHIRING',
            'TIDAK DIHIRING' => 'TIDAK DIHIRING',
        ];
        return $statusMap[$value] ?? $value;
    }

    public function headingRow(): int
    {
        return $this->type == 'non-organic' ? 4 : 1;
    }
}