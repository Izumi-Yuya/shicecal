<?php

namespace Tests\Feature;

use Tests\TestCase;

class BasicTest extends TestCase
{
    /** @test */
    public function root_redirects_to_home()
    {
        $response = $this->get('/');
        
        $response->assertRedirect('/home');
    }
}