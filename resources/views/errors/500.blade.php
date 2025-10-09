<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>サーバーエラー - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="display-1 text-danger">500</h1>
                        <h2>サーバーエラー</h2>
                        <p class="text-muted">申し訳ございません。サーバーでエラーが発生しました。</p>
                        <a href="{{ route('facilities.index') }}" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>ホームに戻る
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>