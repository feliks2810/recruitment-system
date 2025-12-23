<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

use Illuminate\Foundation\Testing\WithoutMiddleware;

class VacancyProposalControllerTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;


    public function test_user_with_permission_can_propose_a_vacancy()
    {
        // Create a user and department
        $department = Department::factory()->create();
        $user = User::factory()->create([
            'department_id' => $department->id,
        ]);

        // Create and assign the 'propose-vacancy' permission
        $permission = Permission::firstOrCreate(['name' => 'propose-vacancy']);
        $role = Role::firstOrCreate(['name' => 'department-head']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        // Acting as the created user
        $this->actingAs($user);

        // Mock the storage
        Storage::fake('public');

        // Data for the new proposal
        $positionName = 'Software Engineer';
        $neededCount = 3;
        $file = UploadedFile::fake()->create('document.pdf', 100);

        // Send the request
        $response = $this->post(route('proposals.store'), [
            'position_name' => $positionName,
            'needed_count' => $neededCount,
            'document' => $file,
        ]);

        // Assertions
        $response->assertRedirect(route('proposals.create'));
        $response->assertSessionHas('success', 'Vacancy proposal submitted successfully.');

        $this->assertDatabaseHas('vacancies', [
            'name' => $positionName,
            'department_id' => $department->id,
            'proposed_needed_count' => $neededCount,
            'proposal_status' => 'pending',
            'proposed_by_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('manpower_request_files', [
            'user_id' => $user->id,
            'stage' => 'initial',
        ]);

        $this->assertDatabaseHas('vacancy_proposal_histories', [
            'user_id' => $user->id,
            'status' => 'pending',
            'proposed_needed_count' => $neededCount,
        ]);

        // Assert file was stored
        Storage::disk('public')->assertExists('manpower_requests/' . $file->hashName());
    }
}