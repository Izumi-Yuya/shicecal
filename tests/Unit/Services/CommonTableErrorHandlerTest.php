<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CommonTableErrorHandler;
use Exception;
use Illuminate\Support\Facades\Log;

class CommonTableErrorHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
    }

    /**
     * 基本的なエラーハンドリングのテスト
     */
    public function test_handles_basic_error()
    {
        $error = new Exception('テストエラー');
        $result = CommonTableErrorHandler::handleError($error);

        $this->assertArrayHasKey('error_id', $result);
        $this->assertArrayHasKey('user_message', $result);
        $this->assertArrayHasKey('technical_message', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('level', $result);

        $this->assertEquals('システムエラーが発生しました', $result['user_message']);
        $this->assertEquals('テストエラー', $result['technical_message']);
        $this->assertEquals(CommonTableErrorHandler::TYPE_SYSTEM, $result['type']);
        $this->assertEquals(CommonTableErrorHandler::LEVEL_ERROR, $result['level']);

        // ログが記録されることを確認
        Log::shouldHaveReceived('error')->once();
    }

    /**
     * 文字列エラーのハンドリングテスト
     */
    public function test_handles_string_error()
    {
        $error = 'テスト文字列エラー';
        $result = CommonTableErrorHandler::handleError($error, CommonTableErrorHandler::TYPE_DATA);

        $this->assertEquals('データの読み込み中にエラーが発生しました', $result['user_message']);
        $this->assertEquals('テスト文字列エラー', $result['technical_message']);
        $this->assertEquals(CommonTableErrorHandler::TYPE_DATA, $result['type']);

        Log::shouldHaveReceived('error')->once();
    }

    /**
     * バリデーションエラーのハンドリングテスト
     */
    public function test_handles_validation_errors()
    {
        $validationResult = [
            'valid' => false,
            'errors' => [
                'エラー1',
                'エラー2'
            ],
            'warnings' => [
                '警告1'
            ]
        ];

        $result = CommonTableErrorHandler::handleValidationErrors($validationResult);

        $this->assertArrayHasKey('error_id', $result);
        $this->assertEquals('データの形式に問題があります', $result['user_message']);
        $this->assertEquals(CommonTableErrorHandler::TYPE_VALIDATION, $result['type']);
        $this->assertEquals(CommonTableErrorHandler::LEVEL_ERROR, $result['level']);
        $this->assertEquals($validationResult['errors'], $result['errors']);
        $this->assertEquals($validationResult['warnings'], $result['warnings']);

        Log::shouldHaveReceived('warning')->once();
    }

    /**
     * バリデーション警告のみのハンドリングテスト
     */
    public function test_handles_validation_warnings_only()
    {
        $validationResult = [
            'valid' => true,
            'errors' => [],
            'warnings' => [
                '警告1',
                '警告2'
            ]
        ];

        $result = CommonTableErrorHandler::handleValidationErrors($validationResult);

        $this->assertEquals(CommonTableErrorHandler::LEVEL_WARNING, $result['level']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals($validationResult['warnings'], $result['warnings']);

        Log::shouldHaveReceived('info')->once();
    }

    /**
     * レンダリングエラーのハンドリングテスト
     */
    public function test_handles_rendering_error()
    {
        $exception = new Exception('レンダリングエラー');
        $data = [['test' => 'data']];
        $options = ['option1' => 'value1'];

        $result = CommonTableErrorHandler::handleRenderingError($exception, $data, $options);

        $this->assertEquals('テーブルの表示中にエラーが発生しました', $result['user_message']);
        $this->assertEquals(CommonTableErrorHandler::TYPE_RENDERING, $result['type']);
        $this->assertArrayHasKey('data_count', $result['context']);
        $this->assertArrayHasKey('options', $result['context']);
        $this->assertEquals(1, $result['context']['data_count']);

        Log::shouldHaveReceived('error')->once();
    }

    /**
     * データエラーのハンドリングテスト
     */
    public function test_handles_data_error()
    {
        $message = 'データエラーメッセージ';
        $data = [
            ['type' => 'standard', 'cells' => [['label' => 'test', 'value' => 'value']]],
            ['type' => 'single', 'cells' => []]
        ];

        $result = CommonTableErrorHandler::handleDataError($message, $data);

        $this->assertEquals('データの読み込み中にエラーが発生しました', $result['user_message']);
        $this->assertEquals(CommonTableErrorHandler::TYPE_DATA, $result['type']);
        $this->assertArrayHasKey('data_structure', $result['context']);

        $dataStructure = $result['context']['data_structure'];
        $this->assertEquals(2, $dataStructure['total_rows']);
        $this->assertContains('standard', $dataStructure['row_types']);
        $this->assertContains('single', $dataStructure['row_types']);

        Log::shouldHaveReceived('error')->once();
    }

    /**
     * フォールバックデータ生成のテスト
     */
    public function test_generates_fallback_data()
    {
        $title = 'テストタイトル';
        $message = 'カスタムメッセージ';
        $showRetry = true;

        $result = CommonTableErrorHandler::generateFallbackData($title, $message, $showRetry);

        $this->assertEquals($title, $result['title']);
        $this->assertEquals($message, $result['message']);
        $this->assertEquals($showRetry, $result['show_retry']);
        $this->assertArrayHasKey('timestamp', $result);
    }

    /**
     * デバッグ用フォーマットのテスト
     */
    public function test_formats_for_debug()
    {
        $errorData = [
            'error_id' => 'TEST_123',
            'type' => CommonTableErrorHandler::TYPE_VALIDATION,
            'level' => CommonTableErrorHandler::LEVEL_ERROR,
            'user_message' => 'ユーザーメッセージ',
            'technical_message' => '技術的メッセージ',
            'context' => ['key' => 'value']
        ];

        $result = CommonTableErrorHandler::formatForDebug($errorData);

        $this->assertEquals('TEST_123', $result['error_id']);
        $this->assertEquals(CommonTableErrorHandler::TYPE_VALIDATION, $result['type']);
        $this->assertEquals(CommonTableErrorHandler::LEVEL_ERROR, $result['level']);
        $this->assertEquals('ユーザーメッセージ', $result['user_message']);
        $this->assertEquals('技術的メッセージ', $result['technical_message']);
        $this->assertEquals(['key' => 'value'], $result['context']);
        $this->assertArrayHasKey('timestamp', $result);
    }

    /**
     * アラートクラス取得のテスト
     */
    public function test_gets_alert_class()
    {
        $this->assertEquals('alert-danger', CommonTableErrorHandler::getAlertClass(CommonTableErrorHandler::LEVEL_ERROR));
        $this->assertEquals('alert-warning', CommonTableErrorHandler::getAlertClass(CommonTableErrorHandler::LEVEL_WARNING));
        $this->assertEquals('alert-info', CommonTableErrorHandler::getAlertClass(CommonTableErrorHandler::LEVEL_INFO));
        $this->assertEquals('alert-secondary', CommonTableErrorHandler::getAlertClass('unknown'));
    }

    /**
     * アイコンクラス取得のテスト
     */
    public function test_gets_icon_class()
    {
        $this->assertEquals('fas fa-exclamation-triangle', CommonTableErrorHandler::getIconClass(CommonTableErrorHandler::LEVEL_ERROR));
        $this->assertEquals('fas fa-exclamation-circle', CommonTableErrorHandler::getIconClass(CommonTableErrorHandler::LEVEL_WARNING));
        $this->assertEquals('fas fa-info-circle', CommonTableErrorHandler::getIconClass(CommonTableErrorHandler::LEVEL_INFO));
        $this->assertEquals('fas fa-question-circle', CommonTableErrorHandler::getIconClass('unknown'));
    }

    /**
     * エラーレベル定数のテスト
     */
    public function test_error_level_constants()
    {
        $this->assertEquals('error', CommonTableErrorHandler::LEVEL_ERROR);
        $this->assertEquals('warning', CommonTableErrorHandler::LEVEL_WARNING);
        $this->assertEquals('info', CommonTableErrorHandler::LEVEL_INFO);
    }

    /**
     * エラータイプ定数のテスト
     */
    public function test_error_type_constants()
    {
        $this->assertEquals('validation', CommonTableErrorHandler::TYPE_VALIDATION);
        $this->assertEquals('rendering', CommonTableErrorHandler::TYPE_RENDERING);
        $this->assertEquals('data', CommonTableErrorHandler::TYPE_DATA);
        $this->assertEquals('system', CommonTableErrorHandler::TYPE_SYSTEM);
    }

    /**
     * データ構造分析のテスト
     */
    public function test_analyzes_data_structure()
    {
        $data = [
            [
                'type' => 'standard',
                'cells' => [
                    ['type' => 'text', 'label' => 'Label1', 'value' => 'Value1'],
                    ['type' => 'email', 'label' => 'Label2', 'value' => 'test@example.com']
                ]
            ],
            [
                'type' => 'single',
                'cells' => [
                    ['type' => 'url', 'label' => 'Label3', 'value' => 'https://example.com']
                ]
            ],
            [
                'type' => 'standard',
                'cells' => [] // 空のセル配列
            ]
        ];

        $result = CommonTableErrorHandler::handleDataError('テスト', $data);
        $analysis = $result['context']['data_structure'];

        $this->assertEquals(3, $analysis['total_rows']);
        $this->assertContains('standard', $analysis['row_types']);
        $this->assertContains('single', $analysis['row_types']);
        $this->assertContains('text', $analysis['cell_types']);
        $this->assertContains('email', $analysis['cell_types']);
        $this->assertContains('url', $analysis['cell_types']);
        $this->assertTrue($analysis['has_empty_rows']);
        $this->assertEquals(2, $analysis['max_cells_per_row']);
        $this->assertEquals(0, $analysis['min_cells_per_row']);
    }
}