<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Vacancy;

class VacancySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seed vacancies untuk setiap departemen
     */
    public function run(): void
    {
        $departments = Department::all()->keyBy('name');

        $vacancies = [
            // ========================================
            // 1. Batam Production
            // ========================================
            ['name' => 'Production Officer', 'department_id' => $departments['Batam Production']->id, 'is_active' => true],

            // ========================================
            // 2. Batam QA & QC
            // ========================================
            ['name' => 'QC Blasting & Painting', 'department_id' => $departments['Batam QA & QC']->id, 'is_active' => true],
            ['name' => 'QC Hull & Outfitting', 'department_id' => $departments['Batam QA & QC']->id, 'is_active' => true],
            ['name' => 'QC Piping & Mechanic', 'department_id' => $departments['Batam QA & QC']->id, 'is_active' => true],
            ['name' => 'Quality Assurance', 'department_id' => $departments['Batam QA & QC']->id, 'is_active' => true],

            // ========================================
            // 3. Engineering
            // ========================================
            ['name' => 'Marine Engineer', 'department_id' => $departments['Engineering']->id, 'is_active' => true],
            ['name' => 'Design Engineer', 'department_id' => $departments['Engineering']->id, 'is_active' => true],
            ['name' => 'Design Engineer Naval', 'department_id' => $departments['Engineering']->id, 'is_active' => true],
            ['name' => 'Drafter', 'department_id' => $departments['Engineering']->id, 'is_active' => true],

            // ========================================
            // 4. Finance & Accounting
            // ========================================
            ['name' => 'Staff', 'department_id' => $departments['Finance & Accounting']->id, 'is_active' => true],
            ['name' => 'Finance & Administration Officer', 'department_id' => $departments['Finance & Accounting']->id, 'is_active' => true],
            ['name' => 'Finance Administrator', 'department_id' => $departments['Finance & Accounting']->id, 'is_active' => true],
            ['name' => 'Accounting Staff', 'department_id' => $departments['Finance & Accounting']->id, 'is_active' => true],
            ['name' => 'Finance & Administration', 'department_id' => $departments['Finance & Accounting']->id, 'is_active' => true],

            // ========================================
            // 5. HCGAESRIT
            // ========================================
            ['name' => 'Section Head', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'HCGAESR Staff', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'HCGA Administrator', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'IT Officer', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'GA Personnel', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'GA Building Maintainer', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'Cleaner', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'EHS Officer', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'Safety Officer', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'Safety Inspector', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'Sustainability Staff', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'Security', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'Human Capital Development', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'EHSSR Officer', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'GAEHSSR Officer', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'HC Operation Staff', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],
            ['name' => 'Sustainability', 'department_id' => $departments['HCGAESRIT']->id, 'is_active' => true],

            // ========================================
            // 6. Strategic Planning Function
            // ========================================
            ['name' => 'Business Development', 'department_id' => $departments['Strategic Planning Function']->id, 'is_active' => true],
            ['name' => 'Quality Risk Management Staff', 'department_id' => $departments['Strategic Planning Function']->id, 'is_active' => true],

            // ========================================
            // 7. Procurement & Subcontractor
            // ========================================
            ['name' => 'Procurement Officer', 'department_id' => $departments['Procurement & Subcontractor']->id, 'is_active' => true],
            ['name' => 'Procurement Administrator', 'department_id' => $departments['Procurement & Subcontractor']->id, 'is_active' => true],
            ['name' => 'Procurement Staff', 'department_id' => $departments['Procurement & Subcontractor']->id, 'is_active' => true],

            // ========================================
            // 8. Production Control
            // ========================================
            ['name' => 'Planning & Production Control', 'department_id' => $departments['Production Control']->id, 'is_active' => true],

            // ========================================
            // 9. PE & Facility
            // ========================================
            ['name' => 'Facility Maintenance Officer', 'department_id' => $departments['PE & Facility']->id, 'is_active' => true],
            ['name' => 'Electrical Officer', 'department_id' => $departments['PE & Facility']->id, 'is_active' => true],

            // ========================================
            // 10. Warehouse & Inventory
            // ========================================
            ['name' => 'Warehouse & Inventory Management Staff', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],
            ['name' => 'SCM Staff', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],
            ['name' => 'Receiving Personnel', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],
            ['name' => 'Binning Personnel', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],
            ['name' => 'Stock Taking Personnel', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],
            ['name' => 'Issuing & Supply Personnel', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],
            ['name' => 'Inventory Management Staff', 'department_id' => $departments['Warehouse & Inventory']->id, 'is_active' => true],

            // ========================================
            // 11. Marketing, Business Development & Sales Ship Building
            // ========================================
            ['name' => 'Business Consultant', 'department_id' => $departments['Marketing, Business Development & Sales Ship Building']->id, 'is_active' => true],
            ['name' => 'Marketing Operation', 'department_id' => $departments['Marketing, Business Development & Sales Ship Building']->id, 'is_active' => true],
        ];

        foreach ($vacancies as $vacancy) {
            Vacancy::firstOrCreate($vacancy);
        }

        $this->command->info('✅ Vacancies seeded successfully:');
        $this->command->info('   Total vacancies: ' . Vacancy::count());
    }
}
