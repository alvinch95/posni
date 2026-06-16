<?php

namespace Tests\Feature\Chen;

use App\Chen\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Chen\ChenTestCase;

class CreateUserCommandTest extends ChenTestCase
{
    public function test_command_creates_a_chen_user(): void
    {
        $this->artisan('chen:user', ['username' => 'owner'])
            ->expectsQuestion('Name', 'Owner')
            ->expectsQuestion('Password', 'topsecret1')
            ->assertExitCode(0);

        $user = User::where('username', 'owner')->first();
        $this->assertNotNull($user);
        $this->assertSame('Owner', $user->name);
        $this->assertTrue(Hash::check('topsecret1', $user->password));
    }

    public function test_command_rejects_duplicate_username(): void
    {
        User::factory()->create(['username' => 'dupe']);

        $this->artisan('chen:user', ['username' => 'dupe'])
            ->assertExitCode(1);
    }

    public function test_command_rejects_empty_password(): void
    {
        $this->artisan('chen:user', ['username' => 'blank'])
            ->expectsQuestion('Name', 'Blank')
            ->expectsQuestion('Password', '')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('chen_users', ['username' => 'blank']);
    }
}
