<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum chỉ bật session/cookie stateful auth cho request có header
        // Referer/Origin khớp SANCTUM_STATEFUL_DOMAINS — test client không tự
        // gửi header này, nên set mặc định ở đây để mọi test coi là request
        // từ frontend Next.js (localhost:3000, theo config/sanctum.php).
        $this->withHeader('Referer', 'http://localhost:3000');
    }
}
