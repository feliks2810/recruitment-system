<?php

namespace Database\Factories;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition()
    {
        return [
            'nama' => $this->faker->name,
            'alamat_email' => $this->faker->unique()->safeEmail,
            'source' => $this->faker->randomElement(['Jobstreet', 'LinkedIn', 'Website']),
            'airsys_internal' => 'Yes',
        ];
    }
}
