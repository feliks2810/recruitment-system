<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CandidateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->can('edit-candidates');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $candidateId = $this->route('candidate') ? $this->route('candidate')->id : null;
        
        return [
            'nama' => 'required|string|max:255',
            'alamat_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('candidates')->ignore($candidateId),
            ],
            'applicant_id' => [
                'required',
                'string',
                'max:50',
            ],
            'vacancy' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'internal_position' => 'nullable|string|max:255',
            'on_process_by' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
            'jk' => 'nullable|in:L,P,Laki-laki,Perempuan',
            'tanggal_lahir' => 'nullable|date|before:today',
            'jenjang_pendidikan' => 'nullable|string|max:50',
            'perguruan_tinggi' => 'nullable|string|max:255',
            'jurusan' => 'nullable|string|max:255',
            'ipk' => 'nullable|numeric|min:0|max:4',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB
            'flk' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB
            'airsys_internal' => 'required|in:Yes,No',
            
            // Stage dates - optional
            'psikotes_date' => 'nullable|date',
            'hc_interview_date' => 'nullable|date',
            'user_interview_date' => 'nullable|date',
            'bodgm_interview_date' => 'nullable|date',
            'offering_letter_date' => 'nullable|date',
            'mcu_date' => 'nullable|date',
            'hiring_date' => 'nullable|date',
            
            // Stage results - optional
            'psikotes_result' => 'nullable|in:LULUS,TIDAK LULUS,DIPERTIMBANGKAN',
            'hc_interview_status' => 'nullable|in:DISARANKAN,TIDAK DISARANKAN,DIPERTIMBANGKAN,CANCEL',
            'user_interview_status' => 'nullable|in:DISARANKAN,TIDAK DISARANKAN,DIPERTIMBANGKAN,CANCEL',
            'bod_interview_status' => 'nullable|in:DISARANKAN,TIDAK DISARANKAN,DIPERTIMBANGKAN,CANCEL',
            'offering_letter_status' => 'nullable|in:DITERIMA,DITOLAK,SENT',
            'mcu_status' => 'nullable|in:LULUS,TIDAK LULUS',
            'hiring_status' => 'nullable|in:HIRED,TIDAK DIHIRING',
            
            // Notes - optional
            'psikotes_notes' => 'nullable|string|max:1000',
            'hc_interview_notes' => 'nullable|string|max:1000',
            'user_interview_notes' => 'nullable|string|max:1000',
            'bod_interview_notes' => 'nullable|string|max:1000',
            'offering_letter_notes' => 'nullable|string|max:1000',
            'mcu_notes' => 'nullable|string|max:1000',
            'hiring_notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama kandidat harus diisi.',
            'alamat_email.required' => 'Email kandidat harus diisi.',
            'alamat_email.email' => 'Format email tidak valid.',
            'alamat_email.unique' => 'Email ini sudah digunakan oleh kandidat lain.',
            'applicant_id.required' => 'Applicant ID harus diisi.',
            'applicant_id.unique' => 'Applicant ID ini sudah digunakan.',
            'vacancy.required' => 'Vacancy harus diisi.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'ipk.min' => 'IPK minimal adalah 0.',
            'ipk.max' => 'IPK maksimal adalah 4.',
            'cv.mimes' => 'File CV harus berformat PDF, DOC, atau DOCX.',
            'cv.max' => 'Ukuran file CV maksimal 5MB.',
            'flk.mimes' => 'File FLK harus berformat PDF, DOC, atau DOCX.',
            'flk.max' => 'Ukuran file FLK maksimal 5MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama kandidat',
            'alamat_email' => 'email',
            'applicant_id' => 'Applicant ID',
            'vacancy' => 'Vacancy',
            'jk' => 'jenis kelamin',
            'tanggal_lahir' => 'tanggal lahir',
            'jenjang_pendidikan' => 'jenjang pendidikan',
            'perguruan_tinggi' => 'perguruan tinggi',
            'cv' => 'file CV',
            'flk' => 'file FLK',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        abort(403, 'Anda tidak memiliki izin untuk menambah/mengedit data kandidat.');
    }
}