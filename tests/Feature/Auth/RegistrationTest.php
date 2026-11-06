<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '012345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'phone' => '012345678',
        ]);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_register_with_uppercase_or_whitespace_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'Kongchhun',
            'email' => '  KongchhunYT@gmail.com  ',
            'phone' => '012345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'kongchhunyt@gmail.com',
            'name' => 'Kongchhun',
        ]);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_requires_phone_number(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertGuest();
    }
}
