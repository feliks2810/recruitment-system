<?php

namespace Database\Factories;

use App\Models\Vacancy;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VacancyFactory extends Factory
{
    protected $model = Vacancy::class;

    public function definition()
    {
        return [
            'name' => $this->faker->jobTitle,
            'department_id' => Department::factory(),
            'needed_count' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'proposal_status' => null,
            'proposed_needed_count' => null,
            'proposed_by_user_id' => null,
            'rejection_reason' => null,
        ];
    }
}
