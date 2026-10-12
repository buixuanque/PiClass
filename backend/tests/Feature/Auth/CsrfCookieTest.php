<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class CsrfCookieTest extends TestCase
{
    public function test_csrf_cookie_endpoint_sets_cookie(): void
    {
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertNoContent();
        $response->assertCookie('XSRF-TOKEN');
    }
}
