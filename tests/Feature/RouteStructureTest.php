<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Facility;
use App\Models\LandInfo;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

class RouteStructureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $adminUser;
    protected $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'editor']);
        $this->adminUser = User::factory()->create(['role' => 'admin']);
        $this->facility = Facility::factory()->create();
    }

    /** @test */
    public function authentication_routes_work_correctly()
    {
        // Test login page
        $response = $this->get('/login');
        $response->assertStatus(200);

        // Test root redirect
        $response = $this->get('/');
        $response->assertRedirect('/login');

        // Test logout (requires authentication)
        $response = $this->actingAs($this->user)->post('/logout');
        $response->assertRedirect();
    }

    /** @test */
    public function facility_routes_work_correctly()
    {
        $this->actingAs($this->user);

        // Test facility index
        $response = $this->get(route('facilities.index'));
        $response->assertStatus(200);

        // Test facility show
        $response = $this->get(route('facilities.show', $this->facility));
        $response->assertStatus(200);

        // Test basic info routes
        $response = $this->get(route('facilities.basic-info', $this->facility));
        $response->assertStatus(200);

        $response = $this->get(route('facilities.edit-basic-info', $this->facility));
        $response->assertStatus(200);
    }

    /** @test */
    public function land_info_nested_routes_work_correctly()
    {
        $this->actingAs($this->user);

        // Test land info show
        $response = $this->get(route('facilities.land-info.show', $this->facility));
        $response->assertStatus(200);

        // Test land info edit
        $response = $this->get(route('facilities.land-info.edit', $this->facility));
        $response->assertStatus(200);

        // Test land info status
        $response = $this->get(route('facilities.land-info.status', $this->facility));
        $response->assertStatus(200);

        // Test documents index
        $response = $this->get(route('facilities.land-info.documents.index', $this->facility));
        $response->assertStatus(200);
    }

    /** @test */
    public function export_routes_work_correctly()
    {
        $this->actingAs($this->user);

        // Test PDF export routes
        $response = $this->get(route('export.pdf.index'));
        $response->assertStatus(200);

        $response = $this->get(route('export.pdf.single', $this->facility));
        $response->assertStatus(200);

        // Test CSV export routes
        $response = $this->get(route('export.csv.index'));
        $response->assertStatus(200);

        $response = $this->get(route('export.csv.favorites.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function comment_routes_work_correctly()
    {
        $this->actingAs($this->user);

        // Test comment management routes
        $response = $this->get(route('comments.index'));
        $response->assertStatus(200);

        $response = $this->get(route('comments.my-comments'));
        $response->assertStatus(200);

        $response = $this->get(route('comments.assigned'));
        $response->assertStatus(200);

        $response = $this->get(route('comments.status-dashboard'));
        $response->assertStatus(200);

        // Test facility comments
        $response = $this->get(route('facilities.comments.index', [$this->facility, 'basic-info']));
        $response->assertStatus(200);
    }

    /** @test */
    public function notification_routes_work_correctly()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('notifications.index'));
        $response->assertStatus(200);

        $response = $this->get(route('notifications.unread-count'));
        $response->assertStatus(200);

        $response = $this->get(route('notifications.recent'));
        $response->assertStatus(200);
    }

    /** @test */
    public function my_page_routes_work_correctly()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('my-page.index'));
        $response->assertStatus(200);

        $response = $this->get(route('my-page.my-comments'));
        $response->assertStatus(200);
    }

    /** @test */
    public function maintenance_routes_work_correctly()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('maintenance.index'));
        $response->assertStatus(200);

        $response = $this->get(route('maintenance.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function annual_confirmation_routes_work_correctly()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('annual-confirmation.index'));
        $response->assertStatus(200);

        $response = $this->get(route('annual-confirmation.create'));
        $response->assertStatus(200);

        $response = $this->get(route('annual-confirmation.facilities'));
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_routes_require_admin_role()
    {
        // Test with regular user - should be forbidden
        $this->actingAs($this->user);

        $response = $this->get('/admin/users');
        $response->assertStatus(403);

        $response = $this->get('/admin/settings');
        $response->assertStatus(403);

        $response = $this->get('/admin/logs');
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_routes_work_with_admin_user()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/admin/users');
        $response->assertStatus(200);

        $response = $this->get('/admin/settings');
        $response->assertStatus(200);

        $response = $this->get('/admin/settings/general');
        $response->assertStatus(200);

        $response = $this->get('/admin/settings/security');
        $response->assertStatus(200);

        $response = $this->get('/admin/logs');
        $response->assertStatus(200);

        $response = $this->get('/admin/logs/activity');
        $response->assertStatus(200);

        $response = $this->get('/admin/logs/system');
        $response->assertStatus(200);
    }

    /** @test */
    public function backward_compatibility_redirects_work()
    {
        $this->actingAs($this->user);

        // Test land-info redirects
        $response = $this->get("/land-info/{$this->facility->id}");
        $response->assertRedirect(route('facilities.land-info.show', $this->facility));

        $response = $this->get("/land-info/{$this->facility->id}/edit");
        $response->assertRedirect(route('facilities.land-info.edit', $this->facility));

        // Test export redirects
        $response = $this->get('/pdf-export');
        $response->assertRedirect(route('export.pdf.index'));

        $response = $this->get('/csv-export');
        $response->assertRedirect(route('export.csv.index'));

        $response = $this->get('/csv-export/favorites');
        $response->assertRedirect(route('export.csv.favorites.index'));

        // Test admin redirect
        $response = $this->actingAs($this->adminUser)->get('/admin');
        $response->assertRedirect(route('admin.users.index'));
    }

    /** @test */
    public function all_routes_require_authentication()
    {
        $protectedRoutes = [
            '/facilities',
            '/export/pdf',
            '/export/csv',
            '/comments',
            '/notifications',
            '/my-page',
            '/maintenance',
            '/annual-confirmation',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $this->assertTrue(
                $response->isRedirect() && str_contains($response->headers->get('Location'), 'login'),
                "Route {$route} should redirect to login when not authenticated"
            );
        }
    }

    /** @test */
    public function route_names_follow_conventions()
    {
        $expectedRoutePatterns = [
            // Facility routes
            'facilities.index',
            'facilities.show',
            'facilities.create',
            'facilities.store',
            'facilities.edit',
            'facilities.update',
            'facilities.destroy',
            'facilities.basic-info',
            'facilities.land-info.show',
            'facilities.land-info.edit',
            'facilities.land-info.documents.index',

            // Export routes
            'export.pdf.index',
            'export.pdf.single',
            'export.csv.index',
            'export.csv.favorites.index',

            // Comment routes
            'comments.index',
            'comments.my-comments',
            'comments.assigned',

            // Notification routes
            'notifications.index',
            'notifications.unread-count',

            // Admin routes
            'admin.users.show',
            'admin.settings.index',
            'admin.logs.index',
        ];

        $registeredRoutes = collect(Route::getRoutes())->pluck('action.as')->filter();

        foreach ($expectedRoutePatterns as $pattern) {
            $this->assertTrue(
                $registeredRoutes->contains($pattern),
                "Route name '{$pattern}' should be registered"
            );
        }
    }

    /** @test */
    public function nested_routes_have_correct_parameters()
    {
        $this->actingAs($this->user);

        // Test that nested routes properly bind parameters
        $landInfoRoute = route('facilities.land-info.show', $this->facility);
        $this->assertStringContains("/facilities/{$this->facility->id}/land-info", $landInfoRoute);

        $documentsRoute = route('facilities.land-info.documents.index', $this->facility);
        $this->assertStringContains("/facilities/{$this->facility->id}/land-info/documents", $documentsRoute);

        $commentsRoute = route('facilities.comments.index', [$this->facility, 'basic-info']);
        $this->assertStringContains("/facilities/{$this->facility->id}/comments/basic-info", $commentsRoute);
    }

    /** @test */
    public function route_middleware_is_properly_applied()
    {
        $routes = Route::getRoutes();

        // Check that admin routes have role:admin middleware
        $adminRoutes = collect($routes)->filter(function ($route) {
            return str_starts_with($route->uri(), 'admin/');
        });

        foreach ($adminRoutes as $route) {
            $middleware = $route->middleware();
            $this->assertContains('auth', $middleware, "Admin route should have auth middleware");
            $this->assertTrue(
                in_array('role:admin', $middleware) || in_array('role', $middleware),
                "Admin route should have role middleware"
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
}
