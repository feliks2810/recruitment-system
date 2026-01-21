<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition()
    {
        return [
            'candidate_id' => Candidate::factory(),
            'vacancy_id' => Vacancy::factory(),
            'overall_status' => 'PROSES',
        ];
    }
}
