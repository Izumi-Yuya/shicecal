<?php

namespace Tests\Unit\Services;

use App\Services\ValueFormatter;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * セルタイプ別フォーマッターの詳細テスト
 */
class ValueFormatterCellTypesTest extends TestCase
{
    /**
     * @test
     * @group text-formatter
     */
    public function it_formats_text_with_various_options()
    {
        // 基本テキスト
        $result = ValueFormatter::format('基本テキスト', 'text');
        $this->assertEquals('基本テキスト', $result);

        // HTMLエスケープ
        $result = ValueFormatter::format('<b>Bold</b> & "quoted"', 'text');
        $this->assertEquals('&lt;b&gt;Bold&lt;/b&gt; &amp; &quot;quoted&quot;', $result);

        // 最大長制限
        $result = ValueFormatter::format('これは非常に長いテキストです', 'text', ['max_length' => 8]);
        $this->assertEquals('これは非常に長い...', $result);

        // 空値処理
        $result = ValueFormatter::format(null, 'text');
        $this->assertEquals('未設定', $result);

        // カスタム空値テキスト
        $result = ValueFormatter::format('', 'text', ['empty_text' => 'データなし']);
        $this->assertEquals('データなし', $result);
    }

    /**
     * @test
     * @group badge-formatter
     */
    public function it_formats_badges_with_various_options()
    {
        // 基本バッジ
        $result = ValueFormatter::format('Active', 'badge');
        $this->assertEquals('<span class="badge bg-primary">Active</span>', $result);

        // カスタムバッジクラス
        $result = ValueFormatter::format('Success', 'badge', ['badge_class' => 'badge bg-success']);
        $this->assertEquals('<span class="badge bg-success">Success</span>', $result);

        // 自動クラス判定
        $result = ValueFormatter::format('有効', 'badge', ['auto_class' => true]);
        $this->assertEquals('<span class="badge bg-success">有効</span>', $result);

        $result = ValueFormatter::format('無効', 'badge', ['auto_class' => true]);
        $this->assertEquals('<span class="badge bg-danger">無効</span>', $result);

        $result = ValueFormatter::format('保留', 'badge', ['auto_class' => true]);
        $this->assertEquals('<span class="badge bg-warning">保留</span>', $result);

        // 日本語ステータス
        $result = ValueFormatter::format('情報', 'badge', ['auto_class' => true]);
        $this->assertEquals('<span class="badge bg-info">情報</span>', $result);
    }

    /**
     * @test
     * @group email-formatter
     */
    public function it_formats_emails_with_various_options()
    {
        // 基本メール
        $result = ValueFormatter::format('user@example.com', 'email');
        $expected = '<a href="mailto:user@example.com" class="text-decoration-none"><i class="fas fa-envelope me-1"></i>user@example.com</a>';
        $this->assertEquals($expected, $result);

        // アイコンなし
        $result = ValueFormatter::format('user@example.com', 'email', ['show_icon' => false]);
        $expected = '<a href="mailto:user@example.com" class="text-decoration-none">user@example.com</a>';
        $this->assertEquals($expected, $result);

        // 日本語ドメイン対応
        $result = ValueFormatter::format('テスト@example.co.jp', 'email');
        $expected = '<a href="mailto:テスト@example.co.jp" class="text-decoration-none"><i class="fas fa-envelope me-1"></i>テスト@example.co.jp</a>';
        $this->assertEquals($expected, $result);

        // HTMLエスケープ
        $result = ValueFormatter::format('test+tag@example.com', 'email');
        $expected = '<a href="mailto:test+tag@example.com" class="text-decoration-none"><i class="fas fa-envelope me-1"></i>test+tag@example.com</a>';
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @group url-formatter
     */
    public function it_formats_urls_with_various_options()
    {
        // HTTPSプロトコル付きURL
        $result = ValueFormatter::format('https://www.example.com', 'url');
        $expected = '<a href="https://www.example.com" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>https://www.example.com</a>';
        $this->assertEquals($expected, $result);

        // プロトコルなしURL（自動追加）
        $result = ValueFormatter::format('www.example.com', 'url');
        $expected = '<a href="https://www.example.com" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>www.example.com</a>';
        $this->assertEquals($expected, $result);

        // カスタム表示テキスト
        $result = ValueFormatter::format('https://www.example.com', 'url', ['display_text' => 'サイトを見る']);
        $expected = '<a href="https://www.example.com" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>サイトを見る</a>';
        $this->assertEquals($expected, $result);

        // アイコンなし
        $result = ValueFormatter::format('https://www.example.com', 'url', ['show_icon' => false]);
        $expected = '<a href="https://www.example.com" target="_blank" class="text-decoration-none">https://www.example.com</a>';
        $this->assertEquals($expected, $result);

        // カスタムターゲット
        $result = ValueFormatter::format('https://www.example.com', 'url', ['target' => '_self']);
        $expected = '<a href="https://www.example.com" target="_self" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>https://www.example.com</a>';
        $this->assertEquals($expected, $result);

        // 日本語URL
        $result = ValueFormatter::format('https://日本語.example.com', 'url');
        $expected = '<a href="https://日本語.example.com" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>https://日本語.example.com</a>';
        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @group date-formatter
     */
    public function it_formats_dates_with_various_options()
    {
        $testDate = '2023-12-25 15:30:45';

        // デフォルト日本語フォーマット
        $result = ValueFormatter::format($testDate, 'date');
        $this->assertEquals('2023年12月25日', $result);

        // カスタムフォーマット
        $result = ValueFormatter::format($testDate, 'date', ['format' => 'Y/m/d']);
        $this->assertEquals('2023/12/25', $result);

        // 時刻付きフォーマット
        $result = ValueFormatter::format($testDate, 'date', ['format' => 'Y年m月d日 H:i']);
        $this->assertEquals('2023年12月25日 15:30', $result);

        // Carbonインスタンス
        $carbon = Carbon::create(2023, 12, 25, 15, 30, 45);
        $result = ValueFormatter::format($carbon, 'date');
        $this->assertEquals('2023年12月25日', $result);

        // 不正な日付
        $result = ValueFormatter::format('invalid-date', 'date');
        $this->assertEquals('invalid-date', $result);

        // 空の日付
        $result = ValueFormatter::format(null, 'date');
        $this->assertEquals('未設定', $result);

        // 日本語フォーマットショートカット
        $result = ValueFormatter::formatDate($testDate, ValueFormatter::getJapaneseDateFormat('short'));
        $this->assertEquals('2023/12/25', $result);

        $result = ValueFormatter::formatDate($testDate, ValueFormatter::getJapaneseDateFormat('medium'));
        $this->assertEquals('2023年12月25日', $result);
    }

    /**
     * @test
     * @group currency-formatter
     */
    public function it_formats_currency_with_various_options()
    {
        // 基本通貨フォーマット
        $result = ValueFormatter::format(1000, 'currency');
        $this->assertEquals('1,000円', $result);

        // 小数点付き
        $result = ValueFormatter::format(1000.50, 'currency', ['decimals' => 2]);
        $this->assertEquals('1,000.50円', $result);

        // カスタム通貨
        $result = ValueFormatter::format(1000, 'currency', ['currency' => 'USD']);
        $this->assertEquals('1,000USD', $result);

        // 大きな数値
        $result = ValueFormatter::format(1000000, 'currency');
        $this->assertEquals('1,000,000円', $result);

        // 文字列数値
        $result = ValueFormatter::format('1500', 'currency');
        $this->assertEquals('1,500円', $result);

        // 負の値
        $result = ValueFormatter::format(-1000, 'currency');
        $this->assertEquals('-1,000円', $result);

        // ゼロ
        $result = ValueFormatter::format(0, 'currency');
        $this->assertEquals('0円', $result);

        // 空値
        $result = ValueFormatter::format(null, 'currency');
        $this->assertEquals('未設定', $result);
    }

    /**
     * @test
     * @group number-formatter
     */
    public function it_formats_numbers_with_various_options()
    {
        // 基本数値フォーマット
        $result = ValueFormatter::format(1000, 'number');
        $this->assertEquals('1,000', $result);

        // 小数点付き
        $result = ValueFormatter::format(1000.567, 'number', ['decimals' => 2]);
        $this->assertEquals('1,000.57', $result);

        // 大きな数値
        $result = ValueFormatter::format(1000000, 'number');
        $this->assertEquals('1,000,000', $result);

        // 文字列数値
        $result = ValueFormatter::format('1500.25', 'number', ['decimals' => 1]);
        $this->assertEquals('1,500.3', $result);

        // 負の値
        $result = ValueFormatter::format(-1000, 'number');
        $this->assertEquals('-1,000', $result);

        // ゼロ
        $result = ValueFormatter::format(0, 'number');
        $this->assertEquals('0', $result);

        // 空値
        $result = ValueFormatter::format(null, 'number');
        $this->assertEquals('未設定', $result);
    }

    /**
     * @test
     * @group file-formatter
     */
    public function it_formats_file_links_with_various_types()
    {
        // PDFファイル
        $result = ValueFormatter::format('/documents/report.pdf', 'file');
        $expected = '<a href="/documents/report.pdf" class="text-decoration-none" target="_blank"><i class="fas fa-file-pdf text-danger"></i> report.pdf</a>';
        $this->assertEquals($expected, $result);

        // Wordファイル
        $result = ValueFormatter::format('/documents/document.docx', 'file');
        $expected = '<a href="/documents/document.docx" class="text-decoration-none" target="_blank"><i class="fas fa-file-word text-primary"></i> document.docx</a>';
        $this->assertEquals($expected, $result);

        // Excelファイル
        $result = ValueFormatter::format('/documents/spreadsheet.xlsx', 'file');
        $expected = '<a href="/documents/spreadsheet.xlsx" class="text-decoration-none" target="_blank"><i class="fas fa-file-excel text-success"></i> spreadsheet.xlsx</a>';
        $this->assertEquals($expected, $result);

        // 画像ファイル
        $result = ValueFormatter::format('/images/photo.jpg', 'file');
        $expected = '<a href="/images/photo.jpg" class="text-decoration-none" target="_blank"><i class="fas fa-file-image text-info"></i> photo.jpg</a>';
        $this->assertEquals($expected, $result);

        // アーカイブファイル
        $result = ValueFormatter::format('/archives/backup.zip', 'file');
        $expected = '<a href="/archives/backup.zip" class="text-decoration-none" target="_blank"><i class="fas fa-file-archive text-secondary"></i> backup.zip</a>';
        $this->assertEquals($expected, $result);

        // テキストファイル
        $result = ValueFormatter::format('/documents/readme.txt', 'file');
        $expected = '<a href="/documents/readme.txt" class="text-decoration-none" target="_blank"><i class="fas fa-file-alt text-secondary"></i> readme.txt</a>';
        $this->assertEquals($expected, $result);

        // CSVファイル
        $result = ValueFormatter::format('/data/export.csv', 'file');
        $expected = '<a href="/data/export.csv" class="text-decoration-none" target="_blank"><i class="fas fa-file-csv text-success"></i> export.csv</a>';
        $this->assertEquals($expected, $result);

        // 不明なファイルタイプ
        $result = ValueFormatter::format('/files/unknown.xyz', 'file');
        $expected = '<a href="/files/unknown.xyz" class="text-decoration-none" target="_blank"><i class="fas fa-file text-muted"></i> unknown.xyz</a>';
        $this->assertEquals($expected, $result);

        // カスタム表示名
        $result = ValueFormatter::format('/documents/report.pdf', 'file', ['display_name' => '年次報告書']);
        $expected = '<a href="/documents/report.pdf" class="text-decoration-none" target="_blank"><i class="fas fa-file-pdf text-danger"></i> 年次報告書</a>';
        $this->assertEquals($expected, $result);

        // 日本語ファイル名
        $result = ValueFormatter::format('/documents/報告書.pdf', 'file');
        $expected = '<a href="/documents/報告書.pdf" class="text-decoration-none" target="_blank"><i class="fas fa-file-pdf text-danger"></i> 報告書.pdf</a>';
        $this->assertEquals($expected, $result);

        // 空値
        $result = ValueFormatter::format(null, 'file');
        $this->assertEquals('未設定', $result);
    }

    /**
     * @test
     * @group helper-methods
     */
    public function it_provides_correct_badge_classes()
    {
        $testCases = [
            'success' => 'badge bg-success',
            'active' => 'badge bg-success',
            '有効' => 'badge bg-success',
            'danger' => 'badge bg-danger',
            'error' => 'badge bg-danger',
            'inactive' => 'badge bg-danger',
            '無効' => 'badge bg-danger',
            'warning' => 'badge bg-warning',
            'pending' => 'badge bg-warning',
            '保留' => 'badge bg-warning',
            'info' => 'badge bg-info',
            'information' => 'badge bg-info',
            '情報' => 'badge bg-info',
            'secondary' => 'badge bg-secondary',
            'draft' => 'badge bg-secondary',
            '下書き' => 'badge bg-secondary',
            'primary' => 'badge bg-primary',
            'default' => 'badge bg-primary',
            'unknown' => 'badge bg-primary',
        ];

        foreach ($testCases as $input => $expected) {
            $result = ValueFormatter::getBadgeClass($input);
            $this->assertEquals($expected, $result, "Failed for badge type: {$input}");
        }
    }

    /**
     * @test
     * @group helper-methods
     */
    public function it_provides_correct_japanese_date_formats()
    {
        $testCases = [
            'short' => 'Y/m/d',
            'medium' => 'Y年m月d日',
            'long' => 'Y年m月d日 (D)',
            'full' => 'Y年m月d日 H:i:s',
            'custom' => 'custom',
        ];

        foreach ($testCases as $input => $expected) {
            $result = ValueFormatter::getJapaneseDateFormat($input);
            $this->assertEquals($expected, $result, "Failed for date format: {$input}");
        }
    }

    /**
     * @test
     * @group edge-cases
     */
    public function it_handles_edge_cases_correctly()
    {
        // 非常に長いテキスト
        $longText = str_repeat('あ', 1000);
        $result = ValueFormatter::format($longText, 'text', ['max_length' => 10]);
        $this->assertEquals('ああああああああああ...', $result);

        // 特殊文字を含むURL
        $result = ValueFormatter::format('https://example.com/path?param=value&other=test', 'url');
        $this->assertStringContainsString('https://example.com/path?param=value&amp;other=test', $result);

        // 非常に大きな数値
        $result = ValueFormatter::format(999999999999, 'currency');
        $this->assertEquals('999,999,999,999円', $result);

        // 空白のみの値
        $result = ValueFormatter::format('   ', 'text');
        $this->assertEquals('未設定', $result);

        // ゼロ値の処理
        $result = ValueFormatter::format(0, 'text');
        $this->assertEquals('0', $result);

        // false値の処理
        $result = ValueFormatter::format(false, 'text');
        $this->assertEquals('', $result);
    }
}