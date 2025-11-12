<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vacancy;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use Illuminate\Foundation\Testing\WithoutMiddleware;

use Spatie\Permission\Models\Role;

class VacancyProposalControllerTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_user_can_propose_a_vacancy()
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $user->department_id = $department->id;
        $user->save();

        $vacancy = Vacancy::factory()->create(['department_id' => $department->id]);

        $this->actingAs($user);

        $response = $this->post(route('proposals.store'), [
            'vacancy_id' => $vacancy->id,
            'proposed_needed_count' => 5,
        ]);

        $response->assertRedirect(route('proposals.create'));
        $this->assertDatabaseHas('vacancies', [
            'id' => $vacancy->id,
            'proposal_status' => 'pending',
            'proposed_needed_count' => 5,
        ]);
        $this->assertDatabaseHas('vacancy_proposal_histories', [
            'vacancy_id' => $vacancy->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_hc1_can_approve_a_proposal()
    {
        $role = Role::create(['name' => 'hc1']);
        $role->givePermissionTo('review-vacancy-proposals-step-1');
        $user = User::factory()->create();
        $user->assignRole($role);
        $department = Department::factory()->create();
        $vacancy = Vacancy::factory()->create([
            'department_id' => $department->id,
            'proposal_status' => 'pending',
            'proposed_needed_count' => 5,
        ]);

        $this->actingAs($user);

        $response = $this->patch(route('proposals.approve', $vacancy));

        $response->assertRedirect();
        $this->assertDatabaseHas('vacancies', [
            'id' => $vacancy->id,
            'proposal_status' => 'pending_hc2_approval',
        ]);
        $this->assertDatabaseHas('vacancy_proposal_histories', [
            'vacancy_id' => $vacancy->id,
            'user_id' => $user->id,
            'status' => 'pending_hc2_approval',
        ]);
    }

    public function test_hc2_can_approve_a_proposal()
    {
        $role = Role::create(['name' => 'hc2']);
        $role->givePermissionTo('review-vacancy-proposals-step-2');
        $user = User::factory()->create();
        $user->assignRole($role);
        $department = Department::factory()->create();
        $vacancy = Vacancy::factory()->create([
            'department_id' => $department->id,
            'proposal_status' => 'pending_hc2_approval',
            'needed_count' => 10,
            'proposed_needed_count' => 5,
        ]);

        $this->actingAs($user);

        $response = $this->patch(route('proposals.approve', $vacancy));

        $response->assertRedirect();
        $this->assertDatabaseHas('vacancies', [
            'id' => $vacancy->id,
            'proposal_status' => 'approved',
            'needed_count' => 15,
        ]);
        $this->assertDatabaseHas('vacancy_proposal_histories', [
            'vacancy_id' => $vacancy->id,
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
    }
}
