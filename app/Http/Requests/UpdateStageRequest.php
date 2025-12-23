<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Authorization is handled by the `authorizeCandidate` method in the controller.
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
            'stage' => 'required|string',
            'result' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            'scheduled_date' => 'nullable|date',
            'next_stage_date' => 'nullable|date|required_if:result,LULUS,DISARANKAN,DITERIMA',
        ];
    }
}