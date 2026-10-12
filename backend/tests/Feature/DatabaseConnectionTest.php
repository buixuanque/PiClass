<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function test_can_connect_to_postgres(): void
    {
        $result = DB::select('select 1 as ok');

        $this->assertSame(1, $result[0]->ok);
    }
}
