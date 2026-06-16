<?php

namespace Tests\Feature\Chen;

use App\Chen\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Chen\ChenTestCase;

class CreateUserCommandTest extends ChenTestCase
{
    public function test_command_creates_a_chen_user(): void
    {
        $this->artisan('chen:user', ['email' => 'owner@chen.app'])
            ->expectsQuestion('Name', 'Owner')
            ->expectsQuestion('Password', 'topsecret1')
            ->assertExitCode(0);

        $user = User::where('email', 'owner@chen.app')->first();
        $this->assertNotNull($user);
        $this->assertSame('Owner', $user->name);
        $this->assertTrue(Hash::check('topsecret1', $user->password));
    }

    public function test_command_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'dupe@chen.app']);

        $this->artisan('chen:user', ['email' => 'dupe@chen.app'])
            ->assertExitCode(1);
    }

    public function test_command_rejects_empty_password(): void
    {
        $this->artisan('chen:user', ['email' => 'blank@chen.app'])
            ->expectsQuestion('Name', 'Blank')
            ->expectsQuestion('Password', '')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('chen_users', ['email' => 'blank@chen.app']);
    }
}
