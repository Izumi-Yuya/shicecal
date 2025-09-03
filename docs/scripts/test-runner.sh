#!/bin/bash

# PHP警告を完全に抑制してテストを実行するスクリプト

# 環境変数でPHP警告を抑制
export PHP_INI_SCAN_DIR=""

# PHPの設定を一時的に変更してテストを実行
php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" \
    -d display_errors=0 \
    -d display_startup_errors=0 \
    -d log_errors=0 \
    artisan test "$@"