<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_a_member_and_normalises_email(): void
    {
        $this->postJson('/api/members', ['name' => 'Nimal', 'email' => '  Nimal@Example.com '])
            ->assertCreated()
            ->assertJsonPath('data.email', 'nimal@example.com');
    }

    public function test_email_must_be_unique(): void
    {
        Member::factory()->create(['email' => 'nimal@example.com']);

        // Different capitals: still a duplicate, because the email is lowercased first
        $this->postJson('/api/members', ['name' => 'Nimal 2', 'email' => 'NIMAL@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_it_lists_a_members_loans_and_filters_by_status(): void
    {
        $member = Member::factory()->create();
        Loan::factory()->for($member)->create();               // active
        Loan::factory()->for($member)->returned()->create();   // returned
        Loan::factory()->create();                             // someone else's loan

        $this->getJson("/api/members/{$member->id}/loans")
            ->assertOk()
            ->assertJsonCount(2, 'data');                      // not the other member's loan

        $this->getJson("/api/members/{$member->id}/loans?status=active")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'active');
    }
}