<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_to_onboarding_without_a_session(): void
    {
        $this->get('/')
            ->assertRedirect(route('onboarding.show'));
    }

    public function test_onboarding_page_loads_without_a_session(): void
    {
        $this->get(route('onboarding.show'))
            ->assertOk()
            ->assertSee('Configurar WhatsApp');
    }
}
