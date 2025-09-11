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
     * Switch view mode and wait for completion
     */
    protected function switchViewMode(Browser $browser, string $mode): Browser
    {
        return $browser->click("label[for=\"{$mode}View\"]")
            ->waitFor('.view-toggle-loading-indicator', 5)
            ->waitUntilMissing('.view-toggle-loading-indicator', 15)
            ->waitFor($mode === 'table' ? '.facility-table-view' : '.facility-info-card', 10);
    }

    /**
     * Assert view mode is active
     */
    protected function assertViewModeActive(Browser $browser, string $mode): void
    {
        $browser->assertChecked("#{$mode}View");

        if ($mode === 'table') {
            $browser->assertPresent('.facility-table-view')
                ->assertMissing('.facility-info-card');
        } else {
            $browser->assertPresent('.facility-info-card')
                ->assertMissing('.facility-table-view');
        }
    }
}
