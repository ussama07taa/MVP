<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_root_url_does_not_return_404(): void
    {
        // GET / now redirects to the dashboard route; without sessions/db it
        // may bubble a redirect or a server error, but it must NOT 404 — that
        // would indicate the routes/web.php file is broken (missing <?php tag).
        $response = $this->get('/');

        $this->assertNotEquals(404, $response->status(), 'GET / must not 404 — routes/web.php is likely broken');
    }
}
