<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_attempts_are_throttled_per_email_and_ip(): void
    {
        User::factory()->create([
            'email' => 'admin@rose.test',
            'password' => 'password',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', [
                'email' => 'admin@rose.test',
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        $this->post('/login', [
            'email' => 'admin@rose.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_the_throttle_does_not_block_a_different_account(): void
    {
        User::factory()->create(['email' => 'admin@rose.test', 'password' => 'password']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', [
                'email' => 'admin@rose.test',
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        // A different email is a different limiter key, so a shared office IP
        // cannot be used to lock an unrelated account out.
        $this->post('/login', [
            'email' => 'someone-else@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect();
    }
}
