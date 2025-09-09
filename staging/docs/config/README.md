# 設定ファイル集

このディレクトリには、Shise-Calの各種サーバー設定ファイルが含まれています。

## 📁 ファイル一覧

### Webサーバー設定
- `nginx.conf` - 本番環境用Nginx設定
- `nginx-test.conf` - テスト環境用Nginx設定

### PHP設定
- `php-fpm.conf` - PHP-FPMプロセス管理設定

### プロセス管理
- `supervisor.conf` - Supervisorによるバックグラウンドプロセス管理設定

## 🔧 使用方法

### Nginx設定の適用
```bash
# 本番環境
sudo cp docs/config/nginx.conf /etc/nginx/sites-available/shisecal
sudo ln -s /etc/nginx/sites-available/shisecal /etc/nginx/sites-enabled/
sudo systemctl reload nginx

# テスト環境
sudo cp docs/config/nginx-test.conf /etc/nginx/sites-available/shisecal-test
sudo ln -s /etc/nginx/sites-available/shisecal-test /etc/nginx/sites-enabled/
sudo systemctl reload nginx
```

### PHP-FPM設定の適用
```bash
sudo cp docs/config/php-fpm.conf /etc/php/8.1/fpm/pool.d/shisecal.conf
sudo systemctl restart php8.1-fpm
```

### Supervisor設定の適用
```bash
sudo cp docs/config/supervisor.conf /etc/supervisor/conf.d/shisecal.conf
sudo supervisorctl reread
sudo supervisorctl update
```

## ⚠️ 注意事項

- 設定ファイルを適用する前に、既存の設定をバックアップしてください
- ドメイン名やパスは環境に合わせて適切に変更してください
- 設定変更後は必ずサービスの再起動を行ってください