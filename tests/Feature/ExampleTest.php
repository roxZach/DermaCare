<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $this->seed();
        $this->get('/')->assertOk()->assertSee('DermaCare');
    }

    public function test_patient_cannot_open_admin_area(): void
    {
        $this->seed();
        $patient = \App\Models\User::where('email', 'patient@dermacare.test')->first();
        $this->actingAs($patient)->get('/admin')->assertForbidden();
    }
}
