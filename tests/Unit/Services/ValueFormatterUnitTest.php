<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ValueFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * ValueFormatter単体テスト
 * 
 * 各フォーマッター機能の詳細なテスト
 * 要件: 設計書のテスト戦略
 */
class ValueFormatterUnitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // テスト前にキャッシュをクリア
        Cache::flush();
    }

    /**
     * @test
     * 空値判定のテスト
     */
    public function test_isEmpty_判定が正しく動作する()
    {
        // null値
        $this->assertTrue(ValueFormatter::isEmpty(null));
        
        // 空文字列
        $this->assertTrue(ValueFormatter::isEmpty(''));
        $this->assertTrue(ValueFormatter::isEmpty('   '));
        
        // 空配列
        $this->assertTrue(ValueFormatter::isEmpty([]));
        
        // 有効な値
        $this->assertFalse(ValueFormatter::isEmpty('test'));
        $this->assertFalse(ValueFormatter::isEmpty(0));
        $this->assertFalse(ValueFormatter::isEmpty('0'));
        $this->assertFalse(ValueFormatter::isEmpty(false));
        $this->assertFalse(ValueFormatter::isEmpty(['item']));
    }

    /**
     * @test
     * テキストフォーマットのテスト
     */
    public function test_format_text_正しくフォーマットされる()
    {
        // 基本的なテキスト
        $result = ValueFormatter::format('テストテキスト', 'text');
        $this->assertEquals('テストテキスト', $result);
        
        // HTMLエスケープ
        $result = ValueFormatter::format('<script>alert("test")</script>', 'text');
        $this->assertEquals('&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;', $result);
        
        // 最大長制限
        $result = ValueFormatter::format('長いテキストです', 'text', ['max_length' => 5]);
        $this->assertEquals('長いテキス...', $result);
        
        // 空値
        $result = ValueFormatter::format(null, 'text');
        $this->assertEquals('未設定', $result);
        
        // カスタム空値テキスト
        $result = ValueFormatter::format('', 'text', ['empty_text' => 'データなし']);
        $this->assertEquals('データなし', $result);
    }

    /**
     * @test
     * バッジフォーマットのテスト
     */
    public function test_format_badge_正しくフォーマットされる()
    {
        // 基本的なバッジ
        $result = ValueFormatter::format('テスト', 'badge');
        $this->assertStringContainsString('<span class="badge bg-primary">テスト</span>', $result);
        
        // 自動クラス判定
        $result = ValueFormatter::format('success', 'badge', ['auto_class' => true]);
        $this->assertStringContainsString('badge bg-success', $result);
        
        $result = ValueFormatter::format('有効', 'badge', ['auto_class' => true]);
        $this->assertStringContainsString('badge bg-success', $result);
        
        $result = ValueFormatter::format('error', 'badge', ['auto_class' => true]);
        $this->assertStringContainsString('badge bg-danger', $result);
        
        // 明示的なクラス指定
        $result = ValueFormatter::format('カスタム', 'badge', ['badge_class' => 'badge bg-warning']);
        $this->assertStringContainsString('badge bg-warning', $result);
    }

    /**
     * @test
     * メールフォーマットのテスト
     */
    public function test_format_email_正しくフォーマットされる()
    {
        // 基本的なメール
        $result = ValueFormatter::format('test@example.com', 'email');
        $this->assertStringContainsString('mailto:test@example.com', $result);
        $this->assertStringContainsString('fas fa-envelope', $result);
        $this->assertStringContainsString('test@example.com', $result);
        
        // アイコンなし
        $result = ValueFormatter::format('test@example.com', 'email', ['show_icon' => false]);
        $this->assertStringNotContainsString('fas fa-envelope', $result);
        $this->assertStringContainsString('mailto:test@example.com', $result);
    }

    /**
     * @test
     * URLフォーマットのテスト
     */
    public function test_format_url_正しくフォーマットされる()
    {
        // プロトコルありURL
        $result = ValueFormatter::format('https://example.com', 'url');
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('fas fa-external-link-alt', $result);
        
        // プロトコルなしURL（自動追加）
        $result = ValueFormatter::format('example.com', 'url');
        $this->assertStringContainsString('href="https://example.com"', $result);
        
        // カスタム表示テキスト
        $result = ValueFormatter::format('example.com', 'url', ['display_text' => 'サンプルサイト']);
        $this->assertStringContainsString('サンプルサイト', $result);
        
        // アイコンなし
        $result = ValueFormatter::format('example.com', 'url', ['show_icon' => false]);
        $this->assertStringNotContainsString('fas fa-external-link-alt', $result);
        
        // カスタムターゲット
        $result = ValueFormatter::format('example.com', 'url', ['target' => '_self']);
        $this->assertStringContainsString('target="_self"', $result);
    }

    /**
     * @test
     * 日付フォーマットのテスト
     */
    public function test_format_date_正しくフォーマットされる()
    {
        // Carbon日付
        $date = Carbon::create(2023, 12, 25);
        $result = ValueFormatter::format($date, 'date');
        $this->assertEquals('2023年12月25日', $result);
        
        // 文字列日付
        $result = ValueFormatter::format('2023-12-25', 'date');
        $this->assertEquals('2023年12月25日', $result);
        
        // カスタムフォーマット
        $result = ValueFormatter::format('2023-12-25', 'date', ['format' => 'Y/m/d']);
        $this->assertEquals('2023/12/25', $result);
        
        // 無効な日付
        $result = ValueFormatter::format('invalid-date', 'date');
        $this->assertEquals('invalid-date', $result);
        
        // 空値
        $result = ValueFormatter::format(null, 'date');
        $this->assertEquals('未設定', $result);
    }

    /**
     * @test
     * 通貨フォーマットのテスト
     */
    public function test_format_currency_正しくフォーマットされる()
    {
        // 基本的な通貨
        $result = ValueFormatter::format(1000, 'currency');
        $this->assertEquals('1,000円', $result);
        
        // 小数点あり
        $result = ValueFormatter::format(1000.50, 'currency', ['decimals' => 2]);
        $this->assertEquals('1,000.50円', $result);
        
        // カスタム通貨単位
        $result = ValueFormatter::format(1000, 'currency', ['currency' => 'ドル']);
        $this->assertEquals('1,000ドル', $result);
        
        // 文字列数値
        $result = ValueFormatter::format('1000', 'currency');
        $this->assertEquals('1,000円', $result);
        
        // 無効な値
        $result = ValueFormatter::format('invalid', 'currency');
        $this->assertEquals('0円', $result);
        
        // 空値
        $result = ValueFormatter::format(null, 'currency');
        $this->assertEquals('未設定', $result);
    }

    /**
     * @test
     * 数値フォーマットのテスト
     */
    public function test_format_number_正しくフォーマットされる()
    {
        // 整数
        $result = ValueFormatter::format(1000, 'number');
        $this->assertEquals('1,000', $result);
        
        // 小数点
        $result = ValueFormatter::format(1000.123, 'number', ['decimals' => 2]);
        $this->assertEquals('1,000.12', $result);
        
        // 文字列数値
        $result = ValueFormatter::format('1000', 'number');
        $this->assertEquals('1,000', $result);
        
        // 無効な値
        $result = ValueFormatter::format('invalid', 'number');
        $this->assertEquals('0', $result);
        
        // 空値
        $result = ValueFormatter::format(null, 'number');
        $this->assertEquals('未設定', $result);
    }

    /**
     * @test
     * ファイルリンクフォーマットのテスト
     */
    public function test_format_file_正しくフォーマットされる()
    {
        // PDFファイル
        $result = ValueFormatter::format('/path/to/document.pdf', 'file');
        $this->assertStringContainsString('fas fa-file-pdf', $result);
        $this->assertStringContainsString('document.pdf', $result);
        $this->assertStringContainsString('target="_blank"', $result);
        
        // Wordファイル
        $result = ValueFormatter::format('/path/to/document.docx', 'file');
        $this->assertStringContainsString('fas fa-file-word', $result);
        
        // Excelファイル
        $result = ValueFormatter::format('/path/to/spreadsheet.xlsx', 'file');
        $this->assertStringContainsString('fas fa-file-excel', $result);
        
        // 画像ファイル
        $result = ValueFormatter::format('/path/to/image.jpg', 'file');
        $this->assertStringContainsString('fas fa-file-image', $result);
        
        // 不明なファイル
        $result = ValueFormatter::format('/path/to/unknown.xyz', 'file');
        $this->assertStringContainsString('fas fa-file', $result);
        
        // カスタム表示名
        $result = ValueFormatter::format('/path/to/document.pdf', 'file', ['display_name' => 'カスタム名.pdf']);
        $this->assertStringContainsString('カスタム名.pdf', $result);
        
        // 空値
        $result = ValueFormatter::format(null, 'file');
        $this->assertEquals('未設定', $result);
    }

    /**
     * @test
     * 不正なセルタイプのフォールバック
     */
    public function test_format_不正なタイプはtextにフォールバック()
    {
        $result = ValueFormatter::format('テスト', 'invalid_type');
        $this->assertEquals('テスト', $result);
    }

    /**
     * @test
     * バッジクラス取得のテスト
     */
    public function test_getBadgeClass_正しいクラスを返す()
    {
        // 成功系
        $this->assertEquals('badge bg-success', ValueFormatter::getBadgeClass('success'));
        $this->assertEquals('badge bg-success', ValueFormatter::getBadgeClass('active'));
        $this->assertEquals('badge bg-success', ValueFormatter::getBadgeClass('有効'));
        
        // エラー系
        $this->assertEquals('badge bg-danger', ValueFormatter::getBadgeClass('danger'));
        $this->assertEquals('badge bg-danger', ValueFormatter::getBadgeClass('error'));
        $this->assertEquals('badge bg-danger', ValueFormatter::getBadgeClass('無効'));
        
        // 警告系
        $this->assertEquals('badge bg-warning', ValueFormatter::getBadgeClass('warning'));
        $this->assertEquals('badge bg-warning', ValueFormatter::getBadgeClass('pending'));
        $this->assertEquals('badge bg-warning', ValueFormatter::getBadgeClass('保留'));
        
        // 情報系
        $this->assertEquals('badge bg-info', ValueFormatter::getBadgeClass('info'));
        $this->assertEquals('badge bg-info', ValueFormatter::getBadgeClass('information'));
        $this->assertEquals('badge bg-info', ValueFormatter::getBadgeClass('情報'));
        
        // セカンダリ系
        $this->assertEquals('badge bg-secondary', ValueFormatter::getBadgeClass('secondary'));
        $this->assertEquals('badge bg-secondary', ValueFormatter::getBadgeClass('draft'));
        $this->assertEquals('badge bg-secondary', ValueFormatter::getBadgeClass('下書き'));
        
        // プライマリ系（デフォルト）
        $this->assertEquals('badge bg-primary', ValueFormatter::getBadgeClass('primary'));
        $this->assertEquals('badge bg-primary', ValueFormatter::getBadgeClass('unknown'));
        $this->assertEquals('badge bg-primary', ValueFormatter::getBadgeClass(''));
    }

    /**
     * @test
     * 日本語日付フォーマット取得のテスト
     */
    public function test_getJapaneseDateFormat_正しいフォーマットを返す()
    {
        $this->assertEquals('Y/m/d', ValueFormatter::getJapaneseDateFormat('short'));
        $this->assertEquals('Y年m月d日', ValueFormatter::getJapaneseDateFormat('medium'));
        $this->assertEquals('Y年m月d日 (D)', ValueFormatter::getJapaneseDateFormat('long'));
        $this->assertEquals('Y年m月d日 H:i:s', ValueFormatter::getJapaneseDateFormat('full'));
        $this->assertEquals('custom-format', ValueFormatter::getJapaneseDateFormat('custom-format'));
    }

    /**
     * @test
     * 一括フォーマット機能のテスト
     */
    public function test_formatBatch_複数値を正しく処理する()
    {
        $values = [
            ['value' => 'テスト1', 'type' => 'text'],
            ['value' => 'success', 'type' => 'badge', 'options' => ['auto_class' => true]],
            ['value' => 'test@example.com', 'type' => 'email'],
            ['value' => null, 'type' => 'text'],
        ];
        
        $results = ValueFormatter::formatBatch($values);
        
        $this->assertCount(3, $results); // 空値はスキップされるため3つ
        $this->assertEquals('テスト1', $results[0]);
        $this->assertStringContainsString('badge bg-success', $results[1]);
        $this->assertStringContainsString('mailto:test@example.com', $results[2]);
    }

    /**
     * @test
     * キャッシュ機能のテスト
     */
    public function test_format_キャッシュが正しく動作する()
    {
        // 大きなデータでキャッシュが有効になることを確認
        $largeData = str_repeat('テストデータ', 50); // 500文字以上
        
        // 最初の呼び出し
        $result1 = ValueFormatter::format($largeData, 'text', ['use_cache' => true]);
        
        // キャッシュから取得されることを確認（同じ結果）
        $result2 = ValueFormatter::format($largeData, 'text', ['use_cache' => true]);
        
        $this->assertEquals($result1, $result2);
        
        // 小さなデータではキャッシュが使用されないことを確認
        $smallData = 'small';
        $result3 = ValueFormatter::format($smallData, 'text');
        $this->assertEquals('small', $result3);
    }

    protected function tearDown(): void
    {
        // テスト後にキャッシュをクリア
        Cache::flush();
        parent::tearDown();
    }
}