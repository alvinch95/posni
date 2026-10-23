<?php

namespace Tests\Feature\Dashboard;

class DashboardAccessTest extends DashboardTestCase
{
    public function test_staff_cannot_open_the_dashboard_or_its_data(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->get('/dashboard')->assertForbidden();
        $this->actingAs($staff)->getJson('/dashboard/data')->assertForbidden();
    }

    public function test_staff_landing_on_home_go_to_attendance(): void
    {
        $this->actingAs($this->staff())->get('/')->assertRedirect('/attendance/checkin');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/login');
    }

    public function test_admin_sees_the_dashboard(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/')->assertOk()->assertSee('Laba Bersih');
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Laba Bersih');
        $this->actingAs($admin)->getJson('/dashboard/data')->assertOk();
    }
}
