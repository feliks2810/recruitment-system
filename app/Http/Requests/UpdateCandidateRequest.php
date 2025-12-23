<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Authorization is handled by the `authorizeCandidate` method in the controller,
        // so we can return true here.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        /** @var \App\Models\Candidate $candidate */
        $candidate = $this->route('candidate');
        $candidateId = $candidate->id;

        return [
            'nama' => 'required|string|max:255',
            'alamat_email' => [
                'required',
                'email',
                Rule::unique('candidates', 'alamat_email')->ignore($candidateId),
            ],
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
        ];
    }
}