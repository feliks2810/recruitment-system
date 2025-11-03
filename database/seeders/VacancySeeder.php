<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Vacancy;

class VacancySeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::all()->keyBy('name');

        $vacancies = [
            // HC, GA, CSR & IT Department
            ['name' => 'Section Head', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'HCGAESR Staff', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'HCGA Administrator', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'IT Officer', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'GA Personnel', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'GA Building Maintainer', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'Cleaner', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],

            // Finance, Accounting & Administration Section
            ['name' => 'Staff', 'department_id' => $departments['Finance & Accounting']->id, 'is_active' => true],
            ['name' => 'Finance & Administration Officer', 'department_id' => $departments['Finance & Accounting']->id, 'is_active' => true],
            ['name' => 'Finance Administrator', 'department_id' => $departments['Finance & Accounting']->id, 'is_active' => true],
            ['name' => 'Accounting Staff', 'department_id' => $departments['Finance & Accounting']->id, 'is_active' => true],

            // Supply Chain Management Section
            ['name' => 'Warehouse & Inventory Management Staff', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],
            ['name' => 'SCM Staff', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => false], // Vacant
            ['name' => 'Procurement Officer', 'department_id' => $departments['Procurement & Subcontractor']->id, 'is_active' => true],
            ['name' => 'Procurement Administrator', 'department_id' => $departments['Procurement & Subcontractor']->id, 'is_active' => true],

            // Warehouse Shipyard Unit
            ['name' => 'Receiving Personnel', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],
            ['name' => 'Binning Personnel', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],
            ['name' => 'Stock Taking Personnel', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],
            ['name' => 'Issuing & Supply Personnel', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],

            // Health & Safety Section
            ['name' => 'EHS Officer', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => false], // Vacant
            ['name' => 'Safety Officer', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'Safety Inspector', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],

            // Sustainability & Security Section
            ['name' => 'Sustainability Staff', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'Security', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
        ];

        foreach ($vacancies as $vacancy) {
            Vacancy::firstOrCreate($vacancy);
        }
    }
}
