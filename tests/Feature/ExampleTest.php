<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_exact_mode()
    {
        $this->artisan('find:supplier')
            ->expectsOutput('DONE. Finished Test.')
            ->assertExitCode(0);
    }

    public function test_fuzzy_mode()
    {
        $this->artisan('find:supplier --fuzzy')
            ->expectsOutput('DONE. Finished Test.')
            ->assertExitCode(0);
    }
}
