<?php

namespace Tests\Feature\Auth;

use Laravel\Fortify\Features;
use Tests\TestCase;

class FortifyRegistrationFeatureTest extends TestCase
{
    public function test_public_registration_is_disabled_by_default(): void
    {
        $this->assertFalse(Features::enabled(Features::registration()));
    }

    public function test_public_registration_can_be_enabled_explicitly(): void
    {
        $features = config('fortify.features');

        config(['fortify.features' => [Features::registration()]]);

        $this->assertTrue(Features::enabled(Features::registration()));

        config(['fortify.features' => $features]);
    }
}
