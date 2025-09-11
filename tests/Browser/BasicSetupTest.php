<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BasicSetupTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test basic browser setup and login functionality
     *
     * @test
     */
    public function it_can_access_login_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('LOGIN')
                ->assertPresent('input[name="email"]')
                ->assertPresent('input[name="password"]')
                ->assertPresent('.login-button');
        });
    }

    /**
     * Test user can login and access facilities index
     *
     * @test
     */
    public function it_can_login_and_access_facilities()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit('/login')
                ->type('email', $user->email)
                ->type('password', 'password')
                ->click('.login-button')
                ->waitForLocation('/facilities', 10)
                ->assertPathIs('/facilities');
        });
    }
}
