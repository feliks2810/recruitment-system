<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'Batam Production'],
            ['name' => 'Batam QA & QC'],
            ['name' => 'Engineering'],
            ['name' => 'Finance & Accounting'],
            ['name' => 'HCGAESRIT'],
            ['name' => 'MDRM Legal & Communication Function'],
            ['name' => 'Procurement & Subcontractor'],
            ['name' => 'Production Control'],
            ['name' => 'PE & Facility'],
            ['name' => 'Warehouse & Inventory'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate($department);
        }
    }
}