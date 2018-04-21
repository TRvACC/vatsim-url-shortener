<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Url;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function organization_has_urls()
    {
        $organization = create(Organization::class);
        $url = create(Url::class, ['organization_id' => $organization->id]);
        $this->assertEquals($url->id, $organization->urls->first()->id);
        $this->assertEquals(1, $organization->urls->count());
    }

    /** @test */
    function organization_has_managers()
    {
        $organization = create(Organization::class);
        $user = create(User::class);
        DB::table($organization->managers()->getTable())->insert([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => OrganizationUser::ROLE_MANAGER,
        ]);
        $this->assertEquals($user->id, $organization->managers->first()->id);
        $this->assertEquals(1, $organization->managers->count());
    }

    /** @test */
    function organization_has_members()
    {
        $organization = create(Organization::class);
        $user = create(User::class);
        DB::table($organization->members()->getTable())->insert([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => OrganizationUser::ROLE_MEMBER,
        ]);
        $this->assertEquals($user->id, $organization->members->first()->id);
        $this->assertEquals(1, $organization->members->count());
    }

    /** @test */
    function organization_has_users()
    {
        $organization = create(Organization::class);
        $user = create(User::class);
        DB::table($organization->users()->getTable())->insert([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => OrganizationUser::ROLE_MANAGER,
        ]);
        DB::table($organization->users()->getTable())->insert([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => OrganizationUser::ROLE_MEMBER,
        ]);
        $this->assertEquals($user->id, $organization->users->first()->id);
        $this->assertEquals(2, $organization->users->count());
    }
}
