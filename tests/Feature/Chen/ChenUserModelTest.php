<?php

namespace Tests\Feature\Chen;

use App\Chen\Models\User;
use Tests\Chen\ChenTestCase;

class ChenUserModelTest extends ChenTestCase
{
    public function test_can_create_chen_user_via_factory(): void
    {
        $user = User::factory()->create(['email' => 'a@b.com']);

        $this->assertDatabaseHas('chen_users', ['email' => 'a@b.com']);
        $this->assertSame('chen_users', $user->getTable());
    }

    public function test_chen_guard_is_configured(): void
    {
        $this->assertSame('chen_users', config('auth.guards.chen.provider'));
        $this->assertSame(User::class, config('auth.providers.chen_users.model'));
    }
}
