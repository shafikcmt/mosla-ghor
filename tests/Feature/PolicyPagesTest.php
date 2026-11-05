<?php

namespace Tests\Feature;

use Tests\TestCase;

class PolicyPagesTest extends TestCase
{
    public function test_policy_pages_are_public_and_in_the_sitemap(): void
    {
        $this->artisan('migrate', ['--force' => true]);
        foreach (['legal.refund', 'legal.delivery', 'legal.about'] as $name) {
            $this->get(route($name))->assertOk();
        }
        $this->get('/')->assertOk()->assertSee(route('legal.refund'), false)->assertSee(route('legal.delivery'), false);
        $this->get('/sitemap.xml')->assertOk()->assertSee(route('legal.refund'), false)->assertSee(route('legal.about'), false);
    }
}
