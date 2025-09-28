<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * ValueFormatter - 共通テーブルレイアウトコンポーネント用の値フォーマッター
 *
 * 様々なデータタイプを適切にフォーマットし、空値の判定を行うサービスです。
 * パフォーマンス最適化のためのキャッシュ機能を含みます。
 * 
 * A service that formats various data types and determines empty values with caching optimization.
 */
class ValueFormatter
{
    /**
     * フォーマット結果のキャッシュ時間（分）
     */
    private const CACHE_TTL = 30;

    /**
     * キャッシュキーのプレフィックス
     */
    private const CACHE_PREFIX = 'value_formatter_';

    /**
     * キャッシュを使用する最小データサイズ
     */
    private const MIN_CACHE_SIZE = 100;

    /**
     * 値を指定されたタイプに基づいてフォーマットする
     *
     * @param  mixed  $value  フォーマットする値
     * @param  string  $type  フォーマットタイプ
     * @param  array  $options  追加オプション
     * @return string フォーマット済みの値
     */
    public static function format($value, string $type, array $options = []): string
    {
        // 空値チェック
        if (self::isEmpty($value)) {
            return $options['empty_text'] ?? '未設定';
        }

        // キャッシュキーの生成（大きなデータの場合のみ）
        $useCache = isset($options['use_cache']) ? $options['use_cache'] : self::shouldUseCache($value);
        $cacheKey = null;

        if ($useCache) {
            $cacheKey = self::generateCacheKey($value, $type, $options);
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $result = match ($type) {
            'text' => self::formatText($value, $options),
            'badge' => self::formatBadge($value, $options),
            'email' => self::formatEmail($value, $options),
            'url' => self::formatUrl($value, $options),
            'date' => self::formatDate($value, $options['format'] ?? 'Y年m月d日'),
            'currency' => self::formatCurrency($value, $options),
            'number' => self::formatNumber($value, $options['decimals'] ?? 0),
            'file' => self::formatFileLink($value, $options),
            'file_display' => self::formatFileDisplay($value, $options),
            'label' => self::formatText($value, $options), // ラベルはテキストと同じ処理
            default => self::formatText($value, $options),
        };

        // 結果をキャッシュに保存
        if ($useCache && $cacheKey) {
            Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_TTL));
        }

        return $result;
    }

    /**
     * 複数の値を一括でフォーマットする（パフォーマンス最適化版）
     *
     * @param  array  $values  フォーマットする値の配列
     * @param  array  $options  共通オプション
     * @return array フォーマット済みの値の配列
     */
    public static function formatBatch(array $values, array $options = []): array
    {
        $results = [];
        $cacheKeys = [];
        $uncachedValues = [];

        // キャッシュ可能な値のキーを事前生成
        foreach ($values as $index => $valueData) {
            if (! is_array($valueData) || ! isset($valueData['value'], $valueData['type'])) {
                continue;
            }

            $value = $valueData['value'];
            $type = $valueData['type'];
            $valueOptions = array_merge($options, $valueData['options'] ?? []);

            if (self::isEmpty($value)) {
                $results[$index] = $valueOptions['empty_text'] ?? '未設定';

                continue;
            }

            if (self::shouldUseCache($value)) {
                $cacheKey = self::generateCacheKey($value, $type, $valueOptions);
                $cacheKeys[$index] = $cacheKey;
            } else {
                $uncachedValues[$index] = $valueData;
            }
        }

        // キャッシュから一括取得
        if (! empty($cacheKeys)) {
            $cachedResults = Cache::many($cacheKeys);
            foreach ($cacheKeys as $index => $cacheKey) {
                if (isset($cachedResults[$cacheKey])) {
                    $results[$index] = $cachedResults[$cacheKey];
                } else {
                    $uncachedValues[$index] = $values[$index];
                }
            }
        }

        // キャッシュにない値を処理
        $newCacheData = [];
        foreach ($uncachedValues as $index => $valueData) {
            $value = $valueData['value'];
            $type = $valueData['type'];
            $valueOptions = array_merge($options, $valueData['options'] ?? []);

            $result = match ($type) {
                'text' => self::formatText($value, $valueOptions),
                'badge' => self::formatBadge($value, $valueOptions),
                'email' => self::formatEmail($value, $valueOptions),
                'url' => self::formatUrl($value, $valueOptions),
                'date' => self::formatDate($value, $valueOptions['format'] ?? 'Y年m月d日'),
                'currency' => self::formatCurrency($value, $valueOptions),
                'number' => self::formatNumber($value, $valueOptions['decimals'] ?? 0),
                'file' => self::formatFileLink($value, $valueOptions),
                'file_display' => self::formatFileDisplay($value, $valueOptions),
                'label' => self::formatText($value, $valueOptions), // ラベルはテキストと同じ処理
                default => self::formatText($value, $valueOptions),
            };

            $results[$index] = $result;

            // キャッシュ対象の場合は保存用データに追加
            if (isset($cacheKeys[$index])) {
                $newCacheData[$cacheKeys[$index]] = $result;
            }
        }

        // 新しい結果を一括でキャッシュに保存
        if (! empty($newCacheData)) {
            Cache::putMany($newCacheData, now()->addMinutes(self::CACHE_TTL));
        }

        return $results;
    }

    /**
     * キャッシュを使用すべきかどうかを判定
     *
     * @param  mixed  $value
     */
    private static function shouldUseCache($value): bool
    {
        if (is_string($value)) {
            return mb_strlen($value) > self::MIN_CACHE_SIZE;
        }

        if (is_array($value) || is_object($value)) {
            return strlen(serialize($value)) > self::MIN_CACHE_SIZE;
        }

        return false;
    }

    /**
     * キャッシュキーを生成
     *
     * @param  mixed  $value
     */
    private static function generateCacheKey($value, string $type, array $options): string
    {
        $keyData = [
            'value' => is_string($value) ? md5($value) : md5(serialize($value)),
            'type' => $type,
            'options' => md5(serialize($options)),
        ];

        return self::CACHE_PREFIX.md5(serialize($keyData));
    }

    /**
     * 値が空かどうかを判定する
     *
     * @param  mixed  $value  判定する値
     * @return bool 空の場合true
     */
    public static function isEmpty($value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value)) {
            if (empty($value)) {
                return true;
            }
            // file_displayタイプの配列の場合、filenameが空なら空とみなす
            if (isset($value['filename']) && empty(trim($value['filename']))) {
                return true;
            }
        }

        return false;
    }

    /**
     * テキストをフォーマットする
     *
     * @param  mixed  $value
     */
    private static function formatText($value, array $options = []): string
    {
        // 配列の場合は適切な文字列表現に変換
        if (is_array($value)) {
            // ファイル表示データの場合
            if (isset($value['filename'])) {
                return htmlspecialchars($value['filename'], ENT_QUOTES, 'UTF-8');
            }
            // その他の配列の場合はJSON形式で表示
            $text = json_encode($value, JSON_UNESCAPED_UNICODE);
        } else {
            $text = (string) $value;
        }

        // HTMLエスケープ
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // 最大長制限
        if (isset($options['max_length']) && mb_strlen($text) > $options['max_length']) {
            $text = mb_substr($text, 0, $options['max_length']).'...';
        }

        return $text;
    }

    /**
     * バッジをフォーマットする
     *
     * @param  mixed  $value
     */
    private static function formatBadge($value, array $options = []): string
    {
        $text = self::formatText($value, $options);

        // 文字列値を取得（バッジクラス判定用）
        $stringValue = is_array($value) ? (isset($value['filename']) ? $value['filename'] : json_encode($value, JSON_UNESCAPED_UNICODE)) : (string) $value;

        // バッジクラスの決定（優先順位: 明示的指定 > 特別なタイプ > 自動判定 > デフォルト）
        if (isset($options['badge_class'])) {
            $badgeClass = self::getSpecialBadgeClass($options['badge_class'], $stringValue);
        } elseif (isset($options['auto_class']) && $options['auto_class']) {
            $badgeClass = self::getBadgeClass($stringValue);
        } else {
            $badgeClass = 'badge bg-primary';
        }

        return sprintf('<span class="%s">%s</span>', $badgeClass, $text);
    }

    /**
     * メールアドレスをフォーマットする
     *
     * @param  mixed  $value
     */
    private static function formatEmail($value, array $options = []): string
    {
        $email = self::formatText($value, $options);
        $icon = $options['show_icon'] ?? true;

        $iconHtml = $icon ? '<i class="fas fa-envelope me-1"></i>' : '';

        return sprintf(
            '<a href="mailto:%s" class="text-decoration-none">%s%s</a>',
            $email,
            $iconHtml,
            $email
        );
    }

    /**
     * URLをフォーマットする
     *
     * @param  mixed  $value
     */
    private static function formatUrl($value, array $options = []): string
    {
        $url = (string) $value;

        // URLにプロトコルがない場合は追加
        if (! preg_match('/^https?:\/\//', $url)) {
            $url = 'https://'.$url;
        }

        $displayText = $options['display_text'] ?? $value;
        $icon = $options['show_icon'] ?? true;
        $target = $options['target'] ?? '_blank';

        $iconHtml = $icon ? '<i class="fas fa-external-link-alt me-1"></i>' : '';

        return sprintf(
            '<a href="%s" target="%s" class="text-decoration-none">%s%s</a>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            $target,
            $iconHtml,
            htmlspecialchars($displayText, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * 日付をフォーマットする
     *
     * @param  mixed  $date
     */
    public static function formatDate($date, string $format = 'Y年m月d日'): string
    {
        if (self::isEmpty($date)) {
            return '未設定';
        }

        try {
            if ($date instanceof Carbon) {
                return $date->format($format);
            }

            $carbonDate = Carbon::parse($date);

            return $carbonDate->format($format);
        } catch (\Exception $e) {
            return (string) $date;
        }
    }

    /**
     * 通貨をフォーマットする
     *
     * @param  mixed  $amount
     */
    public static function formatCurrency($amount, array $options = []): string
    {
        if (self::isEmpty($amount)) {
            return '未設定';
        }

        $numericAmount = is_numeric($amount) ? (float) $amount : 0;
        $currency = $options['currency'] ?? '円';
        $decimals = $options['decimals'] ?? 0;

        $formatted = number_format($numericAmount, $decimals);

        return $formatted.$currency;
    }

    /**
     * 数値をフォーマットする
     *
     * @param  mixed  $number
     */
    public static function formatNumber($number, int $decimals = 0): string
    {
        if (self::isEmpty($number)) {
            return '未設定';
        }

        $numericValue = is_numeric($number) ? (float) $number : 0;

        return number_format($numericValue, $decimals);
    }

    /**
     * ファイル表示をフォーマットする（FileHandlingService用のファイル表示データ処理）
     *
     * @param  mixed  $value  FileHandlingServiceから生成されたファイル表示データ
     * @param  array  $options  追加オプション
     */
    private static function formatFileDisplay($value, array $options = []): string
    {
        if (self::isEmpty($value)) {
            return '未設定';
        }

        // FileHandlingServiceからの配列データの場合
        if (is_array($value)) {
            $filename = $value['filename'] ?? '';
            $downloadUrl = $options['download_url'] ?? $value['download_url'] ?? '';
            $icon = $value['icon'] ?? 'fas fa-file';
            $color = $value['color'] ?? 'text-muted';
            $exists = $value['exists'] ?? true;

            if (empty($filename)) {
                return '未設定';
            }

            if (! $exists) {
                return sprintf(
                    '<span class="text-muted"><i class="%s %s me-1"></i>%s <small>(ファイルが見つかりません。)</small></span>',
                    $icon,
                    $color,
                    htmlspecialchars($filename, ENT_QUOTES, 'UTF-8')
                );
            }

            if (empty($downloadUrl)) {
                return sprintf(
                    '<span class="text-muted"><i class="%s %s me-1"></i>%s</span>',
                    $icon,
                    $color,
                    htmlspecialchars($filename, ENT_QUOTES, 'UTF-8')
                );
            }

            $ariaLabel = $options['aria_label'] ?? $filename.'をダウンロード';

            return sprintf(
                '<a href="%s" class="text-decoration-none" aria-label="%s" target="_blank"><i class="%s %s me-1"></i>%s</a>',
                htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8'),
                $icon,
                $color,
                htmlspecialchars($filename, ENT_QUOTES, 'UTF-8')
            );
        }

        // 文字列の場合は通常のファイルリンクとして処理
        return self::formatFileLink($value, $options);
    }

    /**
     * ファイルリンクをフォーマットする
     *
     * @param  mixed  $value
     */
    private static function formatFileLink($value, array $options = []): string
    {
        if (self::isEmpty($value)) {
            return '未設定';
        }

        // 配列データの場合はfile_displayとして処理
        if (is_array($value)) {
            return self::formatFileDisplay($value, $options);
        }

        $fileName = (string) $value;
        $displayName = $options['display_name'] ?? $fileName;
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // ファイルタイプに基づいてアイコンを決定
        $icon = self::getFileIcon($fileExtension);

        // ルートとパラメータが指定されている場合はLaravelルートを使用
        if (isset($options['route']) && isset($options['params'])) {
            $params = array_merge($options['params'], [$fileName]);
            $url = route($options['route'], $params);
        } else {
            $url = $fileName;
        }

        $ariaLabel = $options['aria_label'] ?? $displayName.'をダウンロード';

        return sprintf(
            '<a href="%s" class="text-decoration-none" aria-label="%s" target="_blank">%s%s</a>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8'),
            $icon,
            htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * ファイル拡張子に基づいてアイコンを取得する
     */
    private static function getFileIcon(string $extension): string
    {
        return match ($extension) {
            'pdf' => '<i class="fas fa-file-pdf text-danger"></i>',
            'doc', 'docx' => '<i class="fas fa-file-word text-primary"></i>',
            'xls', 'xlsx' => '<i class="fas fa-file-excel text-success"></i>',
            'ppt', 'pptx' => '<i class="fas fa-file-powerpoint text-warning"></i>',
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg' => '<i class="fas fa-file-image text-info"></i>',
            'zip', 'rar', '7z', 'tar', 'gz' => '<i class="fas fa-file-archive text-secondary"></i>',
            'txt', 'md' => '<i class="fas fa-file-alt text-secondary"></i>',
            'csv' => '<i class="fas fa-file-csv text-success"></i>',
            default => '<i class="fas fa-file text-muted"></i>',
        };
    }

    /**
     * バッジタイプに基づいてCSSクラスを取得する
     */
    public static function getBadgeClass(string $badgeType): string
    {
        return match (strtolower($badgeType)) {
            'success', 'active', '有効' => 'badge bg-success',
            'danger', 'error', 'inactive', '無効' => 'badge bg-danger',
            'warning', 'pending', '保留' => 'badge bg-warning',
            'info', 'information', '情報' => 'badge bg-info',
            'secondary', 'draft', '下書き' => 'badge bg-secondary',
            'primary', 'default' => 'badge bg-primary',
            default => 'badge bg-primary',
        };
    }

    /**
     * 特別なバッジクラスタイプに基づいてCSSクラスを取得する
     */
    private static function getSpecialBadgeClass(string $badgeType, string $value): string
    {
        return match ($badgeType) {
            'availability' => match ($value) {
                '有' => 'badge bg-success',
                '無' => 'badge bg-secondary',
                default => 'badge bg-secondary',
            },
            'legionella_result' => match ($value) {
                '陰性' => 'badge bg-success',
                '陽性' => 'badge bg-warning',
                default => 'badge bg-secondary',
            },
            default => $badgeType, // 通常のCSSクラスとして扱う
        };
    }

    /**
     * 日本語の日付フォーマットオプション
     */
    public static function getJapaneseDateFormat(string $format): string
    {
        return match ($format) {
            'short' => 'Y/m/d',
            'medium' => 'Y年m月d日',
            'long' => 'Y年m月d日 (D)',
            'full' => 'Y年m月d日 H:i:s',
            default => $format,
        };
    }
}
