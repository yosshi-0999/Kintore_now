- git clone https://github.com/yosshi-0999/Kintore_now.git でリポジトリをクローン
- 開いたのちにブランチを任意のものに切り替える
- composer require laravel/sail --devと
    php artisan sail:install　を実行
- docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs でdocker起動（前のものが残ってると動かないことがあるので注意）
- cp .env.example .env で.env作成
- ./vendor/bin/sail up -d
  ./vendor/bin/sail php artisan key:generate
  ./vendor/bin/sail php artisan migrate を順に実行
- ./vendor/bin/sail npm install を実行
- エラーが発生した場合には　./vendor/bin/sail npm install-scripts approve esbuild　実行後にもう一度 npm install すれば大体行ける（エラー出てても）
- ./vendor/bin/sail npm run dev で成功
