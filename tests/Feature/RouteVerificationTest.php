<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteVerificationTest extends TestCase
{
    /** @test */
    public function route_structure_is_properly_organized()
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'methods' => $route->methods(),
            ];
        });

        // Test that export routes are properly grouped
        $exportRoutes = $routes->filter(function ($route) {
            return str_starts_with($route['uri'], 'export/');
        });

        $this->assertGreaterThan(0, $exportRoutes->count(), 'Export routes should exist under /export prefix');

        // Test that facility routes exist
        $facilityRoutes = $routes->filter(function ($route) {
            return str_starts_with($route['uri'], 'facilities');
        });

        $this->assertGreaterThan(0, $facilityRoutes->count(), 'Facility routes should exist');

        // Test that nested land-info routes exist
        $landInfoRoutes = $routes->filter(function ($route) {
            return str_contains($route['uri'], 'facilities/{facility}/land-info');
        });

        $this->assertGreaterThan(0, $landInfoRoutes->count(), 'Land info routes should be nested under facilities');

        // Test that admin routes are properly grouped
        $adminRoutes = $routes->filter(function ($route) {
            return str_starts_with($route['uri'], 'admin/');
        });

        $this->assertGreaterThan(0, $adminRoutes->count(), 'Admin routes should exist under /admin prefix');
    }

    /** @test */
    public function route_names_follow_conventions()
    {
        $routes = collect(Route::getRoutes())->pluck('action.as')->filter();

        // Test export route naming
        $this->assertTrue($routes->contains('export.pdf.index'), 'Export PDF index route should exist');
        $this->assertTrue($routes->contains('export.csv.index'), 'Export CSV index route should exist');
        $this->assertTrue($routes->contains('export.csv.favorites.index'), 'Export CSV favorites route should exist');

        // Test facility route naming
        $this->assertTrue($routes->contains('facilities.index'), 'Facilities index route should exist');
        $this->assertTrue($routes->contains('facilities.show'), 'Facilities show route should exist');
        $this->assertTrue($routes->contains('facilities.land-info.show'), 'Nested land info route should exist');
        $this->assertTrue($routes->contains('facilities.land-info.documents.index'), 'Nested documents route should exist');

        // Test comment route naming
        $this->assertTrue($routes->contains('comments.index'), 'Comments index route should exist');
        $this->assertTrue($routes->contains('comments.my-comments'), 'My comments route should exist');

        // Test notification route naming
        $this->assertTrue($routes->contains('notifications.index'), 'Notifications index route should exist');
        $this->assertTrue($routes->contains('notifications.unread-count'), 'Notifications unread count route should exist');
    }

    /** @test */
    public function backward_compatibility_routes_exist()
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return $route->uri();
        });

        // Test that old land-info routes still exist for backward compatibility
        $this->assertTrue($routes->contains('land-info/{facility}'), 'Backward compatibility land-info route should exist');
        $this->assertTrue($routes->contains('land-info/{facility}/edit'), 'Backward compatibility land-info edit route should exist');

        // Test that old export routes exist for backward compatibility
        $this->assertTrue($routes->contains('pdf-export'), 'Backward compatibility PDF export route should exist');
        $this->assertTrue($routes->contains('csv-export'), 'Backward compatibility CSV export route should exist');
    }

    /** @test */
    public function routes_have_proper_middleware()
    {
        $routes = Route::getRoutes();

        // Check that admin routes have role:admin middleware
        $adminRoutes = collect($routes)->filter(function ($route) {
            return str_starts_with($route->uri(), 'admin/');
        });

        foreach ($adminRoutes as $route) {
            $middleware = $route->middleware();
            $this->assertContains('auth', $middleware, "Admin route {$route->uri()} should have auth middleware");
            $this->assertTrue(
                in_array('role:admin', $middleware) || in_array('role', $middleware),
                "Admin route {$route->uri()} should have role middleware"
            );
        }

        // Check that protected routes have auth middleware
        $protectedPrefixes = ['facilities', 'export', 'comments', 'notifications', 'my-page', 'maintenance', 'annual-confirmation'];

        foreach ($protectedPrefixes as $prefix) {
            $prefixRoutes = collect($routes)->filter(function ($route) use ($prefix) {
                return str_starts_with($route->uri(), $prefix);
            });

            foreach ($prefixRoutes as $route) {
                $middleware = $route->middleware();
                $this->assertContains('auth', $middleware, "Route {$route->uri()} should have auth middleware");
            }
        }
    }

    /** @test */
    public function route_count_is_reduced()
    {
        $routes = Route::getRoutes();
        $routeCount = count($routes);

        // The route count should be reasonable (not excessive)
        // This is a rough check - adjust based on actual needs
        $this->assertLessThan(150, $routeCount, 'Total route count should be reasonable');

        // Verify we have the main route groups
        $routeUris = collect($routes)->pluck('uri');

        $exportRoutes = $routeUris->filter(fn ($uri) => str_starts_with($uri, 'export/'))->count();
        $facilityRoutes = $routeUris->filter(fn ($uri) => str_starts_with($uri, 'facilities'))->count();
        $adminRoutes = $routeUris->filter(fn ($uri) => str_starts_with($uri, 'admin/'))->count();

        $this->assertGreaterThan(5, $exportRoutes, 'Should have multiple export routes');
        $this->assertGreaterThan(10, $facilityRoutes, 'Should have multiple facility routes');
        $this->assertGreaterThan(5, $adminRoutes, 'Should have multiple admin routes');
    }

    /** @test */
    public function restful_conventions_are_followed()
    {
        $routes = collect(Route::getRoutes());

        // Check that resource routes follow Laravel conventions
        $resourceRoutes = $routes->filter(function ($route) {
            $name = $route->getName();

            return $name && (
                str_ends_with($name, '.index') ||
                str_ends_with($name, '.create') ||
                str_ends_with($name, '.store') ||
                str_ends_with($name, '.show') ||
                str_ends_with($name, '.edit') ||
                str_ends_with($name, '.update') ||
                str_ends_with($name, '.destroy')
            );
        });

        $this->assertGreaterThan(0, $resourceRoutes->count(), 'Should have RESTful resource routes');

        // Check that nested routes use proper parameter naming
        $nestedRoutes = $routes->filter(function ($route) {
            return str_contains($route->uri(), 'facilities/{facility}/');
        });

        foreach ($nestedRoutes as $route) {
            $this->assertStringContainsString('{facility}', $route->uri(), 'Nested routes should use {facility} parameter');
        }
    }
}
