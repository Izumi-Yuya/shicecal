<?php

namespace Tests\Feature;

use Tests\TestCase;

class SimpleNavigationTest extends TestCase
{
    /** @test */
    public function navigation_menu_displays_correctly()
    {
        $response = $this->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('施設管理システム');
    }

    /** @test */
    public function navigation_contains_required_elements()
    {
        $response = $this->get('/home');
        
        $response->assertStatus(200);
        $response->assertSee('navbar');
        $response->assertSee('navbar-brand');
    }
}