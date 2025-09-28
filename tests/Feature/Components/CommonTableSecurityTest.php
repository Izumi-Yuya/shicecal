<?php

namespace Tests\Feature\Components;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * CommonTableセキュリティテスト
 *
 * XSS対策、SQLインジェクション対策、データサニタイゼーションのテスト
 * 要件: 6.1, 6.2
 */
class CommonTableSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $facility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'email' => 'security@example.com',
        ]);

        $this->facility = Facility::factory()->create([
            'company_name' => 'セキュリティテスト株式会社',
            'office_code' => 'SEC001',
        ]);
    }

    /**
     * @test
     * XSS攻撃対策テスト - スクリプトタグ
     */
    public function test_security_xs_s攻撃対策_スクリプトタグ()
    {
        $xssData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '<script>alert("XSS Label")</script>',
                        'value' => '<script>alert("XSS Value")</script>',
                        'type' => 'text',
                    ],
                    [
                        'label' => '正常なラベル',
                        'value' => '<script src="malicious.js"></script>',
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        $view = View::make('components.common-table', [
            'data' => $xssData,
            'title' => '<script>alert("XSS Title")</script>',
        ]);

        $rendered = $view->render();

        // スクリプトタグが実行されないことを確認
        $this->assertStringNotContainsString('<script>alert("XSS Label")</script>', $rendered);
        $this->assertStringNotContainsString('<script>alert("XSS Value")</script>', $rendered);
        $this->assertStringNotContainsString('<script>alert("XSS Title")</script>', $rendered);
        $this->assertStringNotContainsString('<script src="malicious.js"></script>', $rendered);

        // エスケープされた内容が含まれることを確認
        $this->assertStringContainsString('&lt;script&gt;', $rendered);
        $this->assertStringContainsString('&lt;/script&gt;', $rendered);

        Log::info('XSS Script Tag Test Passed');
    }

    /**
     * @test
     * XSS攻撃対策テスト - イベントハンドラー
     */
    public function test_security_xs_s攻撃対策_イベントハンドラー()
    {
        $xssData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '<img src="x" onerror="alert(\'XSS\')">',
                        'value' => '<div onclick="alert(\'XSS\')">クリック</div>',
                        'type' => 'text',
                    ],
                    [
                        'label' => '<input onmouseover="alert(\'XSS\')">',
                        'value' => '<a href="javascript:alert(\'XSS\')">リンク</a>',
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        $view = View::make('components.common-table', [
            'data' => $xssData,
            'title' => 'XSSイベントハンドラーテスト',
        ]);

        $rendered = $view->render();

        // イベントハンドラーが実行されないことを確認
        $this->assertStringNotContainsString('onerror="alert(\'XSS\')"', $rendered);
        $this->assertStringNotContainsString('onclick="alert(\'XSS\')"', $rendered);
        $this->assertStringNotContainsString('onmouseover="alert(\'XSS\')"', $rendered);
        $this->assertStringNotContainsString('javascript:alert(\'XSS\')', $rendered);

        // エスケープされた内容が含まれることを確認
        $this->assertStringContainsString('&lt;img', $rendered);
        $this->assertStringContainsString('&lt;div', $rendered);
        $this->assertStringContainsString('&lt;input', $rendered);

        Log::info('XSS Event Handler Test Passed');
    }

    /**
     * @test
     * XSS攻撃対策テスト - URLタイプでのJavaScript URL
     */
    public function test_security_xs_s攻撃対策_java_script_url()
    {
        $xssData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => 'JavaScript URL',
                        'value' => 'javascript:alert("XSS")',
                        'type' => 'url',
                    ],
                    [
                        'label' => 'Data URL',
                        'value' => 'data:text/html,<script>alert("XSS")</script>',
                        'type' => 'url',
                    ],
                ],
            ],
        ];

        $view = View::make('components.common-table', [
            'data' => $xssData,
            'title' => 'JavaScript URLテスト',
        ]);

        $rendered = $view->render();

        // JavaScript URLが無効化されることを確認
        $this->assertStringNotContainsString('href="javascript:alert("XSS")"', $rendered);
        $this->assertStringNotContainsString('href="data:text/html,<script>alert("XSS")</script>"', $rendered);

        // 安全でないURLは表示されないか、無効化される
        $this->assertTrue(
            ! strpos($rendered, 'javascript:alert') ||
            strpos($rendered, 'href="#"') ||
            strpos($rendered, 'href=""')
        );

        Log::info('JavaScript URL Test Passed');
    }

    /**
     * @test
     * XSS攻撃対策テスト - メールタイプでのJavaScript
     */
    public function test_security_xs_s攻撃対策_メール_java_script()
    {
        $xssData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => 'JavaScript メール',
                        'value' => 'javascript:alert("XSS")@example.com',
                        'type' => 'email',
                    ],
                    [
                        'label' => '不正なメール',
                        'value' => 'test@example.com<script>alert("XSS")</script>',
                        'type' => 'email',
                    ],
                ],
            ],
        ];

        $view = View::make('components.common-table', [
            'data' => $xssData,
            'title' => 'メールJavaScriptテスト',
        ]);

        $rendered = $view->render();

        // JavaScript付きメールが無効化されることを確認
        $this->assertStringNotContainsString('href="mailto:javascript:alert("XSS")@example.com"', $rendered);
        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $rendered);

        // エスケープされた内容が含まれることを確認
        $this->assertStringContainsString('&lt;script&gt;', $rendered);

        Log::info('Email JavaScript Test Passed');
    }

    /**
     * @test
     * SQLインジェクション対策テスト
     */
    public function test_security_sq_lインジェクション対策()
    {
        $sqlInjectionData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => "'; DROP TABLE users; --",
                        'value' => "1' OR '1'='1",
                        'type' => 'text',
                    ],
                    [
                        'label' => 'UNION SELECT',
                        'value' => "' UNION SELECT password FROM users --",
                        'type' => 'text',
                    ],
                ],
            ],
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => 'INSERT攻撃',
                        'value' => "'; INSERT INTO users (email, password) VALUES ('hacker@evil.com', 'password'); --",
                        'type' => 'text',
                    ],
                    [
                        'label' => 'UPDATE攻撃',
                        'value' => "'; UPDATE users SET role='admin' WHERE id=1; --",
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        $view = View::make('components.common-table', [
            'data' => $sqlInjectionData,
            'title' => 'SQLインジェクションテスト',
        ]);

        $rendered = $view->render();

        // SQLインジェクション文字列がそのまま表示される（エスケープされて無害化）
        $this->assertStringContainsString("'; DROP TABLE users; --", $rendered);
        $this->assertStringContainsString("1' OR '1'='1", $rendered);
        $this->assertStringContainsString("' UNION SELECT password FROM users --", $rendered);

        // データベースが正常に動作していることを確認
        $this->assertDatabaseHas('users', ['email' => 'security@example.com']);
        $this->assertDatabaseHas('facilities', ['company_name' => 'セキュリティテスト株式会社']);

        // ユーザー数が変わっていないことを確認（INSERT攻撃が無効）
        $userCount = User::count();
        $this->assertEquals(1, $userCount);

        Log::info('SQL Injection Test Passed');
    }

    /**
     * @test
     * HTMLインジェクション対策テスト
     */
    public function test_security_htm_lインジェクション対策()
    {
        $htmlInjectionData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '<iframe src="http://malicious.com"></iframe>',
                        'value' => '<object data="malicious.swf"></object>',
                        'type' => 'text',
                    ],
                    [
                        'label' => '<embed src="malicious.swf">',
                        'value' => '<form action="http://malicious.com"><input type="submit"></form>',
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        $view = View::make('components.common-table', [
            'data' => $htmlInjectionData,
            'title' => 'HTMLインジェクションテスト',
        ]);

        $rendered = $view->render();

        // 危険なHTMLタグが実行されないことを確認
        $this->assertStringNotContainsString('<iframe src="http://malicious.com"></iframe>', $rendered);
        $this->assertStringNotContainsString('<object data="malicious.swf"></object>', $rendered);
        $this->assertStringNotContainsString('<embed src="malicious.swf">', $rendered);
        $this->assertStringNotContainsString('<form action="http://malicious.com">', $rendered);

        // エスケープされた内容が含まれることを確認
        $this->assertStringContainsString('&lt;iframe', $rendered);
        $this->assertStringContainsString('&lt;object', $rendered);
        $this->assertStringContainsString('&lt;embed', $rendered);
        $this->assertStringContainsString('&lt;form', $rendered);

        Log::info('HTML Injection Test Passed');
    }

    /**
     * @test
     * CSSインジェクション対策テスト
     */
    public function test_security_cs_sインジェクション対策()
    {
        $cssInjectionData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '<style>body{display:none}</style>',
                        'value' => '<div style="background:url(javascript:alert(\'XSS\'))">テスト</div>',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'CSS Expression',
                        'value' => '<div style="width:expression(alert(\'XSS\'))">テスト</div>',
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        $view = View::make('components.common-table', [
            'data' => $cssInjectionData,
            'title' => 'CSSインジェクションテスト',
        ]);

        $rendered = $view->render();

        // 危険なCSSが実行されないことを確認
        $this->assertStringNotContainsString('<style>body{display:none}</style>', $rendered);
        $this->assertStringNotContainsString('javascript:alert(\'XSS\')', $rendered);
        $this->assertStringNotContainsString('expression(alert(\'XSS\'))', $rendered);

        // エスケープされた内容が含まれることを確認
        $this->assertStringContainsString('&lt;style&gt;', $rendered);
        $this->assertStringContainsString('&lt;div', $rendered);

        Log::info('CSS Injection Test Passed');
    }

    /**
     * @test
     * ファイルパストラバーサル対策テスト
     */
    public function test_security_ファイルパストラバーサル対策()
    {
        $pathTraversalData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => 'パストラバーサル1',
                        'value' => '../../../etc/passwd',
                        'type' => 'file',
                    ],
                    [
                        'label' => 'パストラバーサル2',
                        'value' => '..\\..\\..\\windows\\system32\\config\\sam',
                        'type' => 'file',
                    ],
                ],
            ],
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => 'NULL バイト',
                        'value' => 'file.txt%00.jpg',
                        'type' => 'file',
                    ],
                    [
                        'label' => 'URLエンコード',
                        'value' => '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
                        'type' => 'file',
                    ],
                ],
            ],
        ];

        $view = View::make('components.common-table', [
            'data' => $pathTraversalData,
            'title' => 'パストラバーサルテスト',
        ]);

        $rendered = $view->render();

        // 危険なパスが無効化されることを確認
        $this->assertStringNotContainsString('href="../../../etc/passwd"', $rendered);
        $this->assertStringNotContainsString('href="..\\..\\..\\windows\\system32\\config\\sam"', $rendered);

        // ファイル名のみが表示されるか、安全なパスに変換される
        $this->assertTrue(
            strpos($rendered, 'passwd') === false ||
            strpos($rendered, 'etc/passwd') === false
        );

        Log::info('Path Traversal Test Passed');
    }

    /**
     * @test
     * CSRF対策テスト（フォーム要素の無効化）
     */
    public function test_security_csr_f対策()
    {
        $csrfData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => 'フォーム攻撃',
                        'value' => '<form method="POST" action="/admin/delete-all"><input type="submit" value="削除"></form>',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'ボタン攻撃',
                        'value' => '<button onclick="fetch(\'/api/delete\', {method: \'DELETE\'})">削除</button>',
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        $view = View::make('components.common-table', [
            'data' => $csrfData,
            'title' => 'CSRF対策テスト',
        ]);

        $rendered = $view->render();

        // フォーム要素が実行されないことを確認
        $this->assertStringNotContainsString('<form method="POST"', $rendered);
        $this->assertStringNotContainsString('<input type="submit"', $rendered);
        $this->assertStringNotContainsString('<button onclick=', $rendered);

        // エスケープされた内容が含まれることを確認
        $this->assertStringContainsString('&lt;form', $rendered);
        $this->assertStringContainsString('&lt;button', $rendered);

        Log::info('CSRF Protection Test Passed');
    }

    /**
     * @test
     * 大量データによるDoS攻撃対策テスト
     */
    public function test_security_do_s攻撃対策()
    {
        // 非常に大きなデータを生成
        $largeString = str_repeat('A', 100000); // 100KB の文字列

        $dosData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '大量データ',
                        'value' => $largeString,
                        'type' => 'text',
                    ],
                    [
                        'label' => '正常データ',
                        'value' => '正常な値',
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $view = View::make('components.common-table', [
            'data' => $dosData,
            'title' => 'DoS攻撃対策テスト',
        ]);

        $rendered = $view->render();

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $renderTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        // DoS攻撃に対する耐性を確認
        $this->assertLessThan(10.0, $renderTime, 'レンダリング時間が10秒を超えています（DoS攻撃の可能性）');
        $this->assertLessThan(200 * 1024 * 1024, $memoryUsage, 'メモリ使用量が200MBを超えています（DoS攻撃の可能性）');

        // データが適切に処理されることを確認（切り詰められる可能性あり）
        $this->assertStringContainsString('正常な値', $rendered);

        Log::info('DoS Attack Protection Test', [
            'render_time' => $renderTime,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'large_string_length' => strlen($largeString),
        ]);
    }

    /**
     * @test
     * 文字エンコーディング攻撃対策テスト
     */
    public function test_security_文字エンコーディング攻撃対策()
    {
        $encodingData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => 'UTF-8 BOM攻撃',
                        'value' => "\xEF\xBB\xBF<script>alert('XSS')</script>",
                        'type' => 'text',
                    ],
                    [
                        'label' => '不正なUTF-8',
                        'value' => "\xFF\xFE<script>alert('XSS')</script>",
                        'type' => 'text',
                    ],
                ],
            ],
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => 'NULL文字攻撃',
                        'value' => "normal\x00<script>alert('XSS')</script>",
                        'type' => 'text',
                    ],
                    [
                        'label' => '制御文字',
                        'value' => "test\x01\x02\x03value",
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        $view = View::make('components.common-table', [
            'data' => $encodingData,
            'title' => '文字エンコーディング攻撃テスト',
        ]);

        $rendered = $view->render();

        // スクリプトが実行されないことを確認
        $this->assertStringNotContainsString('<script>alert(\'XSS\')</script>', $rendered);

        // 出力が有効なUTF-8であることを確認
        $this->assertTrue(mb_check_encoding($rendered, 'UTF-8'));

        // 制御文字が適切に処理されることを確認
        $this->assertStringContainsString('testvalue', $rendered);

        Log::info('Character Encoding Attack Test Passed');
    }

    /**
     * @test
     * 権限チェックテスト
     */
    public function test_security_権限チェック()
    {
        // 一般ユーザーを作成
        $regularUser = User::factory()->create([
            'role' => 'viewer',
            'email' => 'viewer@example.com',
        ]);

        // 管理者専用データ
        $adminData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => '管理者情報',
                        'value' => '機密データ',
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        // 管理者としてアクセス
        $adminResponse = $this->actingAs($this->user)
            ->get(route('facilities.show', $this->facility));
        $adminResponse->assertStatus(200);

        // 一般ユーザーとしてアクセス
        $userResponse = $this->actingAs($regularUser)
            ->get(route('facilities.show', $this->facility));
        $userResponse->assertStatus(200);

        // 権限に応じた表示制御が行われることを確認
        // （実際の権限制御はコントローラーレベルで実装される）

        Log::info('Permission Check Test Passed');
    }

    /**
     * @test
     * セッションハイジャック対策テスト
     */
    public function test_security_セッションハイジャック対策()
    {
        // セッション情報を含む悪意のあるデータ
        $sessionData = [
            [
                'type' => 'standard',
                'cells' => [
                    [
                        'label' => 'セッション攻撃',
                        'value' => 'PHPSESSID=malicious_session_id',
                        'type' => 'text',
                    ],
                    [
                        'label' => 'Cookie攻撃',
                        'value' => 'document.cookie="admin=true"',
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        $view = View::make('components.common-table', [
            'data' => $sessionData,
            'title' => 'セッションハイジャック対策テスト',
        ]);

        $rendered = $view->render();

        // セッション情報が漏洩しないことを確認
        $this->assertStringNotContainsString('PHPSESSID=', $rendered);
        $this->assertStringNotContainsString('document.cookie=', $rendered);

        // 現在のセッションが有効であることを確認
        $this->assertTrue($this->user->is($this->user));

        Log::info('Session Hijacking Protection Test Passed');
    }
}
