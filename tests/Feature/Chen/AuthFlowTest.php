<?php

namespace Tests\Feature\Chen;

use App\Chen\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Chen\ChenTestCase;

class AuthFlowTest extends ChenTestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get($this->chenUrl('/'))
            ->assertRedirect($this->chenUrl('/login'));
    }

    public function test_login_page_renders(): void
    {
        $this->get($this->chenUrl('/login'))
            ->assertOk()
            ->assertSee('Chen');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create(['email' => 'me@chen.app', 'password' => Hash::make('secret123')]);

        $this->post($this->chenUrl('/login'), ['email' => 'me@chen.app', 'password' => 'secret123'])
            ->assertRedirect($this->chenUrl('/'));

        $this->assertAuthenticatedAs(User::first(), 'chen');
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create(['email' => 'me@chen.app', 'password' => Hash::make('secret123')]);

        $this->from($this->chenUrl('/login'))
            ->post($this->chenUrl('/login'), ['email' => 'me@chen.app', 'password' => 'wrong'])
            ->assertRedirect($this->chenUrl('/login'));

        $this->assertGuest('chen');
    }

    public function test_authenticated_user_can_reach_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'chen')
            ->get($this->chenUrl('/'))
            ->assertOk()
            ->assertSee('Chen');
    }

    public function test_logout_ends_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'chen')
            ->post($this->chenUrl('/logout'))
            ->assertRedirect($this->chenUrl('/login'));

        $this->assertGuest('chen');
    }
}
