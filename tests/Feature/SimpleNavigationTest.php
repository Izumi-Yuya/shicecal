<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleNavigationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function home_page_loads_successfully()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/home');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function home_page_contains_system_title()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('施設管理システム');
    }
}