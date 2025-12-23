<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Authorization is handled by middleware in the routes file,
        // so we can return true here. If you have specific authorization
        // logic for this request, you can implement it here.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'nama' => 'required|string|max:255',
            'alamat_email' => 'required|email',
            'applicant_id' => 'required|string|max:255',
            'jk' => 'required|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'department_id' => 'required|exists:departments,id',
            'airsys_internal' => 'required|in:Yes,No',
            'source' => 'nullable|string|max:100',
            'jenjang_pendidikan' => 'nullable|string|max:100',
            'perguruan_tinggi' => 'nullable|string|max:100',
            'jurusan' => 'nullable|string|max:100',
            'ipk' => 'nullable|numeric|min:0|max:4',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'flk' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'vacancy_name' => 'nullable|string|max:255',
            'internal_position' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ];
    }
}