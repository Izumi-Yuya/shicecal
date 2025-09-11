<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ActivityLogHelper;
use Tests\TestCase;

class ActivityLogHelperTest extends TestCase
{
    /**
     * Test get action badge color for known actions.
     */
    public function test_get_action_badge_color_known_actions()
    {
        $this->assertEquals('success', ActivityLogHelper::getActionBadgeColor('login'));
        $this->assertEquals('secondary', ActivityLogHelper::getActionBadgeColor('logout'));
        $this->assertEquals('primary', ActivityLogHelper::getActionBadgeColor('create'));
        $this->assertEquals('info', ActivityLogHelper::getActionBadgeColor('update'));
        $this->assertEquals('danger', ActivityLogHelper::getActionBadgeColor('delete'));
        $this->assertEquals('success', ActivityLogHelper::getActionBadgeColor('approve'));
        $this->assertEquals('warning', ActivityLogHelper::getActionBadgeColor('reject'));
        $this->assertEquals('info', ActivityLogHelper::getActionBadgeColor('export_csv'));
        $this->assertEquals('info', ActivityLogHelper::getActionBadgeColor('export_pdf'));
        $this->assertEquals('secondary', ActivityLogHelper::getActionBadgeColor('download'));
        $this->assertEquals('primary', ActivityLogHelper::getActionBadgeColor('upload'));
        $this->assertEquals('warning', ActivityLogHelper::getActionBadgeColor('update_status'));
        $this->assertEquals('light', ActivityLogHelper::getActionBadgeColor('view'));
        $this->assertEquals('secondary', ActivityLogHelper::getActionBadgeColor('access'));
    }

    /**
     * Test get action badge color for unknown actions.
     */
    public function test_get_action_badge_color_unknown_action()
    {
        $this->assertEquals('secondary', ActivityLogHelper::getActionBadgeColor('unknown_action'));
        $this->assertEquals('secondary', ActivityLogHelper::getActionBadgeColor(''));
        $this->assertEquals('secondary', ActivityLogHelper::getActionBadgeColor('custom_action'));
    }

    /**
     * Test get action name for known actions.
     */
    public function test_get_action_name_known_actions()
    {
        $this->assertEquals('ログイン', ActivityLogHelper::getActionName('login'));
        $this->assertEquals('ログアウト', ActivityLogHelper::getActionName('logout'));
        $this->assertEquals('作成', ActivityLogHelper::getActionName('create'));
        $this->assertEquals('更新', ActivityLogHelper::getActionName('update'));
        $this->assertEquals('削除', ActivityLogHelper::getActionName('delete'));
        $this->assertEquals('閲覧', ActivityLogHelper::getActionName('view'));
        $this->assertEquals('ダウンロード', ActivityLogHelper::getActionName('download'));
        $this->assertEquals('アップロード', ActivityLogHelper::getActionName('upload'));
        $this->assertEquals('CSV出力', ActivityLogHelper::getActionName('export_csv'));
        $this->assertEquals('PDF出力', ActivityLogHelper::getActionName('export_pdf'));
        $this->assertEquals('承認', ActivityLogHelper::getActionName('approve'));
        $this->assertEquals('差戻し', ActivityLogHelper::getActionName('reject'));
        $this->assertEquals('ステータス更新', ActivityLogHelper::getActionName('update_status'));
        $this->assertEquals('アクセス', ActivityLogHelper::getActionName('access'));
    }

    /**
     * Test get action name for unknown actions.
     */
    public function test_get_action_name_unknown_action()
    {
        $this->assertEquals('unknown_action', ActivityLogHelper::getActionName('unknown_action'));
        $this->assertEquals('', ActivityLogHelper::getActionName(''));
        $this->assertEquals('custom_action', ActivityLogHelper::getActionName('custom_action'));
    }

    /**
     * Test get target type name for known types.
     */
    public function test_get_target_type_name_known_types()
    {
        $this->assertEquals('ユーザー', ActivityLogHelper::getTargetTypeName('user'));
        $this->assertEquals('施設', ActivityLogHelper::getTargetTypeName('facility'));
        $this->assertEquals('ファイル', ActivityLogHelper::getTargetTypeName('file'));
        $this->assertEquals('コメント', ActivityLogHelper::getTargetTypeName('comment'));
        $this->assertEquals('修繕履歴', ActivityLogHelper::getTargetTypeName('maintenance_history'));
        $this->assertEquals('年次確認', ActivityLogHelper::getTargetTypeName('annual_confirmation'));
        $this->assertEquals('通知', ActivityLogHelper::getTargetTypeName('notification'));
        $this->assertEquals('システム設定', ActivityLogHelper::getTargetTypeName('system_setting'));
        $this->assertEquals('システム', ActivityLogHelper::getTargetTypeName('system'));
    }

    /**
     * Test get target type name for unknown types.
     */
    public function test_get_target_type_name_unknown_type()
    {
        $this->assertEquals('unknown_type', ActivityLogHelper::getTargetTypeName('unknown_type'));
        $this->assertEquals('', ActivityLogHelper::getTargetTypeName(''));
        $this->assertEquals('custom_type', ActivityLogHelper::getTargetTypeName('custom_type'));
    }

    /**
     * Test all methods are static.
     */
    public function test_all_methods_are_static()
    {
        $reflection = new \ReflectionClass(ActivityLogHelper::class);

        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $this->assertTrue($method->isStatic(), "Method {$method->getName()} should be static");
        }
    }

    /**
     * Test case sensitivity.
     */
    public function test_case_sensitivity()
    {
        // Test that the methods are case-sensitive
        $this->assertEquals('secondary', ActivityLogHelper::getActionBadgeColor('LOGIN')); // uppercase should return default
        $this->assertEquals('LOGIN', ActivityLogHelper::getActionName('LOGIN')); // uppercase should return as-is
        $this->assertEquals('USER', ActivityLogHelper::getTargetTypeName('USER')); // uppercase should return as-is
    }
}
