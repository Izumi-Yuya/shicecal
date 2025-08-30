<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogActivity
{
    protected $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log for authenticated users
        if (Auth::check()) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    /**
     * Log the activity based on the request.
     *
     * @param Request $request
     * @param mixed $response
     * @return void
     */
    protected function logActivity(Request $request, $response): void
    {
        $method = $request->method();
        $route = $request->route();
        
        if (!$route) {
            return;
        }

        $routeName = $route->getName();
        $uri = $request->getRequestUri();

        // Skip logging for certain routes
        if ($this->shouldSkipLogging($routeName, $uri, $method)) {
            return;
        }

        // Determine action and target based on route and method
        $action = $this->determineAction($method, $routeName, $uri);
        $targetType = $this->determineTargetType($routeName, $uri);
        $targetId = $this->extractTargetId($request, $routeName);
        $description = $this->generateDescription($action, $targetType, $request, $routeName);

        // Log the activity
        $this->activityLogService->log(
            $action,
            $targetType,
            $targetId,
            $description,
            $request
        );
    }

    /**
     * Determine if logging should be skipped for this request.
     *
     * @param string|null $routeName
     * @param string $uri
     * @param string $method
     * @return bool
     */
    protected function shouldSkipLogging(?string $routeName, string $uri, string $method): bool
    {
        // Skip GET requests for index/show pages (read operations)
        if ($method === 'GET' && !$this->isImportantGetRequest($routeName, $uri)) {
            return true;
        }

        // Skip AJAX requests for UI updates
        if (request()->ajax() && !$this->isImportantAjaxRequest($routeName, $uri)) {
            return true;
        }

        // Skip asset requests
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/i', $uri)) {
            return true;
        }

        // Skip API health checks or similar
        if (str_contains($uri, '/health') || str_contains($uri, '/status')) {
            return true;
        }

        return false;
    }

    /**
     * Check if this is an important GET request that should be logged.
     *
     * @param string|null $routeName
     * @param string $uri
     * @return bool
     */
    protected function isImportantGetRequest(?string $routeName, string $uri): bool
    {
        // Log downloads
        if (str_contains($uri, '/download') || str_contains($uri, '/export')) {
            return true;
        }

        // Log admin access
        if (str_contains($uri, '/admin')) {
            return true;
        }

        return false;
    }

    /**
     * Check if this is an important AJAX request that should be logged.
     *
     * @param string|null $routeName
     * @param string $uri
     * @return bool
     */
    protected function isImportantAjaxRequest(?string $routeName, string $uri): bool
    {
        // Log status updates
        if (str_contains($uri, '/status') && request()->method() !== 'GET') {
            return true;
        }

        // Log approval actions
        if (str_contains($uri, '/approve') || str_contains($uri, '/reject')) {
            return true;
        }

        return false;
    }

    /**
     * Determine the action based on HTTP method and route.
     *
     * @param string $method
     * @param string|null $routeName
     * @param string $uri
     * @return string
     */
    protected function determineAction(string $method, ?string $routeName, string $uri): string
    {
        // Special actions based on URI patterns
        if (str_contains($uri, '/download')) {
            return 'download';
        }
        
        if (str_contains($uri, '/export')) {
            if (str_contains($uri, '/csv')) {
                return 'export_csv';
            }
            if (str_contains($uri, '/pdf')) {
                return 'export_pdf';
            }
            return 'export';
        }

        if (str_contains($uri, '/approve')) {
            return 'approve';
        }

        if (str_contains($uri, '/reject')) {
            return 'reject';
        }

        if (str_contains($uri, '/upload')) {
            return 'upload';
        }

        // Standard CRUD actions
        switch ($method) {
            case 'POST':
                return 'create';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            case 'GET':
                return 'view';
            default:
                return 'access';
        }
    }

    /**
     * Determine the target type based on route and URI.
     *
     * @param string|null $routeName
     * @param string $uri
     * @return string
     */
    protected function determineTargetType(?string $routeName, string $uri): string
    {
        if (str_contains($uri, '/facilities')) {
            return 'facility';
        }

        if (str_contains($uri, '/users')) {
            return 'user';
        }

        if (str_contains($uri, '/files')) {
            return 'file';
        }

        if (str_contains($uri, '/comments')) {
            return 'comment';
        }

        if (str_contains($uri, '/maintenance')) {
            return 'maintenance_history';
        }

        if (str_contains($uri, '/annual-confirmation')) {
            return 'annual_confirmation';
        }

        if (str_contains($uri, '/notifications')) {
            return 'notification';
        }

        if (str_contains($uri, '/admin') || str_contains($uri, '/settings')) {
            return 'system_setting';
        }

        return 'system';
    }

    /**
     * Extract target ID from request parameters.
     *
     * @param Request $request
     * @param string|null $routeName
     * @return int|null
     */
    protected function extractTargetId(Request $request, ?string $routeName): ?int
    {
        // Try to get ID from route parameters
        $route = $request->route();
        if ($route) {
            $parameters = $route->parameters();
            
            // Common ID parameter names
            $idParams = ['id', 'facility', 'user', 'file', 'comment', 'maintenance', 'notification'];
            
            foreach ($idParams as $param) {
                if (isset($parameters[$param]) && is_numeric($parameters[$param])) {
                    return (int) $parameters[$param];
                }
            }
        }

        return null;
    }

    /**
     * Generate a description for the activity.
     *
     * @param string $action
     * @param string $targetType
     * @param Request $request
     * @param string|null $routeName
     * @return string
     */
    protected function generateDescription(string $action, string $targetType, Request $request, ?string $routeName): string
    {
        $actionMap = [
            'create' => '作成',
            'update' => '更新',
            'delete' => '削除',
            'view' => '閲覧',
            'download' => 'ダウンロード',
            'upload' => 'アップロード',
            'export_csv' => 'CSV出力',
            'export_pdf' => 'PDF出力',
            'export' => '出力',
            'approve' => '承認',
            'reject' => '差戻し',
            'access' => 'アクセス',
        ];

        $targetMap = [
            'facility' => '施設',
            'user' => 'ユーザー',
            'file' => 'ファイル',
            'comment' => 'コメント',
            'maintenance_history' => '修繕履歴',
            'annual_confirmation' => '年次確認',
            'notification' => '通知',
            'system_setting' => 'システム設定',
            'system' => 'システム',
        ];

        $actionText = $actionMap[$action] ?? $action;
        $targetText = $targetMap[$targetType] ?? $targetType;

        return "{$targetText}を{$actionText}しました";
    }
}