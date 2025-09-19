<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CommonTableErrorHandler;
use Illuminate\Support\Facades\Log;
use Exception;
use InvalidArgumentException;

/**
 * CommonTableErrorHandler単体テスト
 * 
 * エラーハンドリング機能の詳細なテスト
 * 要件: 設計書のテスト戦略
 */
class CommonTableErrorHandlerUnitTest extends TestCase
{
    /**
     * @test
     * 基本的なエラーハンドリング
     */
    public function test_handleError_基本的なエラー処理()
    {
        Log::shouldReceive('error')->once();

        $exception = new Exception('テストエラー');
        $result = CommonTableErrorHandler::handleError($exception);

        $this->assertArrayHasKey('error_id', $result);
        $this->assertArrayHasKey('user_message', $result);
        $this->assertArrayHasKey('technical_message', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('level', $result);

        $this->assertEquals('システムエラーが発生しました', $result['user_message']);
        $this->assertEquals('テストエラー', $result['technical_message']);
        $this->assertEquals(CommonTableErrorHandler::TYPE_SYSTEM, $result['type']);
        $this->assertEquals(CommonTableErrorHandler::LEVEL_ERROR, $result['level']);
    }

    /**
     * @test
     * 文字列エラーのハンドリング
     */
    public function test_handleError_文字列エラー処理()
    {
        Log::shouldReceive('error')->once();

        $errorMessage = 'カスタムエラーメッセージ';
        $result = CommonTableErrorHandler::handleError($errorMessage, CommonTableErrorHandler::TYPE_DATA);

        $this->assertEquals('データの読み込み中にエラーが発生しました', $result['user_message']);
        $this->assertEquals($errorMessage, $result['technical_message']);
        $this->assertEquals(CommonTableErrorHandler::TYPE_DATA, $result['type']);
    }

    /**
     * @test
     * エラータイプ別のユーザーメッセージ
     */
    public function test_handleError_エラータイプ別メッセージ()
    {
        Log::shouldReceive('error')->times(4);

        // バリデーションエラー
        $result = CommonTableErrorHandler::handleError('test', CommonTableErrorHandler::TYPE_VALIDATION);
        $this->assertEquals('データの形式に問題があります', $result['user_message']);

        // レンダリングエラー
        $result = CommonTableErrorHandler::handleError('test', CommonTableErrorHandler::TYPE_RENDERING);
        $this->assertEquals('テーブルの表示中にエラーが発生しました', $result['user_message']);

        // データエラー
        $result = CommonTableErrorHandler::handleError('test', CommonTableErrorHandler::TYPE_DATA);
        $this->assertEquals('データの読み込み中にエラーが発生しました', $result['user_message']);

        // システムエラー
        $result = CommonTableErrorHandler::handleError('test', CommonTableErrorHandler::TYPE_SYSTEM);
        $this->assertEquals('システムエラーが発生しました', $result['user_message']);
    }

    /**
     * @test
     * ログレベル別の処理
     */
    public function test_handleError_ログレベル別処理()
    {
        // エラーレベル
        Log::shouldReceive('error')->once();
        $result = CommonTableErrorHandler::handleError('test', CommonTableErrorHandler::TYPE_SYSTEM, [], CommonTableErrorHandler::LEVEL_ERROR);
        $this->assertEquals(CommonTableErrorHandler::LEVEL_ERROR, $result['level']);

        // 警告レベル
        Log::shouldReceive('warning')->once();
        $result = CommonTableErrorHandler::handleError('test', CommonTableErrorHandler::TYPE_SYSTEM, [], CommonTableErrorHandler::LEVEL_WARNING);
        $this->assertEquals(CommonTableErrorHandler::LEVEL_WARNING, $result['level']);

        // 情報レベル
        Log::shouldReceive('info')->once();
        $result = CommonTableErrorHandler::handleError('test', CommonTableErrorHandler::TYPE_SYSTEM, [], CommonTableErrorHandler::LEVEL_INFO);
        $this->assertEquals(CommonTableErrorHandler::LEVEL_INFO, $result['level']);
    }

    /**
     * @test
     * バリデーションエラーのハンドリング
     */
    public function test_handleValidationErrors_エラーありの場合()
    {
        Log::shouldReceive('warning')->once();

        $validationResult = [
            'valid' => false,
            'errors' => ['エラー1', 'エラー2'],
            'warnings' => ['警告1']
        ];

        $result = CommonTableErrorHandler::handleValidationErrors($validationResult);

        $this->assertArrayHasKey('error_id', $result);
        $this->assertEquals('データの形式に問題があります', $result['user_message']);
        $this->assertEquals(['エラー1', 'エラー2'], $result['errors']);
        $this->assertEquals(['警告1'], $result['warnings']);
        $this->assertEquals(CommonTableErrorHandler::TYPE_VALIDATION, $result['type']);
        $this->assertEquals(CommonTableErrorHandler::LEVEL_ERROR, $result['level']);
    }

    /**
     * @test
     * バリデーション警告のみの場合
     */
    public function test_handleValidationErrors_警告のみの場合()
    {
        Log::shouldReceive('info')->once();

        $validationResult = [
            'valid' => true,
            'errors' => [],
            'warnings' => ['警告1', '警告2']
        ];

        $result = CommonTableErrorHandler::handleValidationErrors($validationResult);

        $this->assertEquals(CommonTableErrorHandler::LEVEL_WARNING, $result['level']);
        $this->assertEmpty($result['errors']);
        $this->assertEquals(['警告1', '警告2'], $result['warnings']);
    }

    /**
     * @test
     * レンダリングエラーのハンドリング
     */
    public function test_handleRenderingError_例外処理()
    {
        Log::shouldReceive('error')->once();

        $exception = new InvalidArgumentException('レンダリングエラー');
        $data = [['test' => 'data']];
        $options = ['option1' => 'value1'];

        $result = CommonTableErrorHandler::handleRenderingError($exception, $data, $options);

        $this->assertEquals(CommonTableErrorHandler::TYPE_RENDERING, $result['type']);
        $this->assertEquals('テーブルの表示中にエラーが発生しました', $result['user_message']);
        $this->assertEquals('レンダリングエラー', $result['technical_message']);
        
        // コンテキストの確認
        $this->assertArrayHasKey('context', $result);
        $this->assertEquals(1, $result['context']['data_count']);
        $this->assertEquals($options, $result['context']['options']);
        $this->assertEquals('InvalidArgumentException', $result['context']['exception_class']);
    }

    /**
     * @test
     * データエラーのハンドリング
     */
    public function test_handleDataError_データ分析付き()
    {
        Log::shouldReceive('error')->once();

        $message = 'データ処理エラー';
        $data = [
            ['type' => 'standard', 'cells' => [['type' => 'text']]],
            ['type' => 'single', 'cells' => []],
        ];

        $result = CommonTableErrorHandler::handleDataError($message, $data);

        $this->assertEquals(CommonTableErrorHandler::TYPE_DATA, $result['type']);
        $this->assertEquals('データの読み込み中にエラーが発生しました', $result['user_message']);
        $this->assertEquals($message, $result['technical_message']);
        
        // データ構造分析の確認
        $this->assertArrayHasKey('context', $result);
        $this->assertArrayHasKey('data_structure', $result['context']);
        
        $structure = $result['context']['data_structure'];
        $this->assertEquals(2, $structure['total_rows']);
        $this->assertContains('standard', $structure['row_types']);
        $this->assertContains('single', $structure['row_types']);
        $this->assertTrue($structure['has_empty_rows']);
    }

    /**
     * @test
     * フォールバックデータの生成
     */
    public function test_generateFallbackData_基本的な生成()
    {
        $result = CommonTableErrorHandler::generateFallbackData();

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('show_retry', $result);
        $this->assertArrayHasKey('timestamp', $result);

        $this->assertNull($result['title']);
        $this->assertEquals('データを表示できませんでした', $result['message']);
        $this->assertFalse($result['show_retry']);
    }

    /**
     * @test
     * カスタムフォールバックデータの生成
     */
    public function test_generateFallbackData_カスタム設定()
    {
        $result = CommonTableErrorHandler::generateFallbackData(
            'カスタムタイトル',
            'カスタムメッセージ',
            true
        );

        $this->assertEquals('カスタムタイトル', $result['title']);
        $this->assertEquals('カスタムメッセージ', $result['message']);
        $this->assertTrue($result['show_retry']);
    }

    /**
     * @test
     * デバッグ用フォーマット
     */
    public function test_formatForDebug_完全なフォーマット()
    {
        $errorData = [
            'error_id' => 'TEST_12345',
            'type' => CommonTableErrorHandler::TYPE_VALIDATION,
            'level' => CommonTableErrorHandler::LEVEL_ERROR,
            'user_message' => 'ユーザーメッセージ',
            'technical_message' => '技術的メッセージ',
            'context' => ['key' => 'value']
        ];

        $result = CommonTableErrorHandler::formatForDebug($errorData);

        $this->assertEquals('TEST_12345', $result['error_id']);
        $this->assertEquals(CommonTableErrorHandler::TYPE_VALIDATION, $result['type']);
        $this->assertEquals(CommonTableErrorHandler::LEVEL_ERROR, $result['level']);
        $this->assertEquals('ユーザーメッセージ', $result['user_message']);
        $this->assertEquals('技術的メッセージ', $result['technical_message']);
        $this->assertEquals(['key' => 'value'], $result['context']);
        $this->assertArrayHasKey('timestamp', $result);
    }

    /**
     * @test
     * 不完全なエラーデータのデバッグフォーマット
     */
    public function test_formatForDebug_不完全なデータ()
    {
        $errorData = ['error_id' => 'TEST_12345'];

        $result = CommonTableErrorHandler::formatForDebug($errorData);

        $this->assertEquals('TEST_12345', $result['error_id']);
        $this->assertEquals('unknown', $result['type']);
        $this->assertEquals('unknown', $result['level']);
        $this->assertEquals('N/A', $result['user_message']);
        $this->assertEquals('N/A', $result['technical_message']);
        $this->assertEquals([], $result['context']);
    }

    /**
     * @test
     * アラートクラスの取得
     */
    public function test_getAlertClass_レベル別クラス()
    {
        $this->assertEquals('alert-danger', CommonTableErrorHandler::getAlertClass(CommonTableErrorHandler::LEVEL_ERROR));
        $this->assertEquals('alert-warning', CommonTableErrorHandler::getAlertClass(CommonTableErrorHandler::LEVEL_WARNING));
        $this->assertEquals('alert-info', CommonTableErrorHandler::getAlertClass(CommonTableErrorHandler::LEVEL_INFO));
        $this->assertEquals('alert-secondary', CommonTableErrorHandler::getAlertClass('unknown'));
    }

    /**
     * @test
     * アイコンクラスの取得
     */
    public function test_getIconClass_レベル別アイコン()
    {
        $this->assertEquals('fas fa-exclamation-triangle', CommonTableErrorHandler::getIconClass(CommonTableErrorHandler::LEVEL_ERROR));
        $this->assertEquals('fas fa-exclamation-circle', CommonTableErrorHandler::getIconClass(CommonTableErrorHandler::LEVEL_WARNING));
        $this->assertEquals('fas fa-info-circle', CommonTableErrorHandler::getIconClass(CommonTableErrorHandler::LEVEL_INFO));
        $this->assertEquals('fas fa-question-circle', CommonTableErrorHandler::getIconClass('unknown'));
    }

    /**
     * @test
     * エラーIDの生成
     */
    public function test_handleError_エラーID生成()
    {
        Log::shouldReceive('error')->twice();

        $result1 = CommonTableErrorHandler::handleError('test1');
        $result2 = CommonTableErrorHandler::handleError('test2');

        // エラーIDが存在し、異なることを確認
        $this->assertArrayHasKey('error_id', $result1);
        $this->assertArrayHasKey('error_id', $result2);
        $this->assertNotEquals($result1['error_id'], $result2['error_id']);
        
        // エラーIDの形式確認（CT_で始まる）
        $this->assertStringStartsWith('CT_', $result1['error_id']);
        $this->assertStringStartsWith('CT_', $result2['error_id']);
    }

    /**
     * @test
     * コンテキスト情報の処理
     */
    public function test_handleError_コンテキスト情報()
    {
        Log::shouldReceive('error')->once();

        $context = [
            'user_id' => 123,
            'action' => 'table_render',
            'data_size' => 100
        ];

        $result = CommonTableErrorHandler::handleError('test', CommonTableErrorHandler::TYPE_SYSTEM, $context);

        $this->assertEquals($context, $result['context']);
    }

    /**
     * @test
     * デバッグモードの影響
     */
    public function test_handleError_デバッグモード設定()
    {
        Log::shouldReceive('error')->twice();

        // デバッグモードON
        config(['app.debug' => true]);
        $result = CommonTableErrorHandler::handleError('test');
        $this->assertTrue($result['show_details']);

        // デバッグモードOFF
        config(['app.debug' => false]);
        $result = CommonTableErrorHandler::handleError('test');
        $this->assertFalse($result['show_details']);
    }
}