<?php

namespace Tests\Browser\Traits;

use App\Models\Facility;
use Laravel\Dusk\Browser;

trait FacilityViewTestTrait
{
    /**
     * Navigate to facility page and wait for view toggle
     */
    protected function navigateToFacilityWithToggle(Browser $browser, Facility $facility): Browser
    {
        return $browser->loginAs($this->user)
            ->visit(route('facilities.show', $facility))
            ->waitFor('.view-toggle-container', 10);
    }

    /**
     * Assert card view is active
     */
    protected function assertCardViewActive(Browser $browser): void
    {
        $browser->assertChecked('#cardView')
            ->assertPresent('.facility-info-card');
    }
}
