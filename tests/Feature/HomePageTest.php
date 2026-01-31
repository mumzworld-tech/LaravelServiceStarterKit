<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    /**
     * Test home page returns successful response.
     */
    public function test_home_page_returns_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Test home page returns HTML content.
     */
    public function test_home_page_returns_html_content(): void
    {
        $response = $this->get('/');

        $contentType = strtolower($response->headers->get('Content-Type'));
        $this->assertEquals('text/html; charset=utf-8', $contentType);
    }
}
