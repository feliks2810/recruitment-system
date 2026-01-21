<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationStage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationStageFactory extends Factory
{
    protected $model = ApplicationStage::class;

    public function definition()
    {
        return [
            'application_id' => Application::factory(),
            'stage_name' => 'psikotes',
            'status' => 'LULUS',
            'scheduled_date' => now(),
        ];
    }
}
