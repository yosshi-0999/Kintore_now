# Kintore_now（筋トレ自己管理アプリ）

筋トレを効果的に続けるための自己管理 Web アプリです。
やった種目と使った筋肉を記録すると、**休ませるべき筋肉**と**今使える筋肉**がわかり、使える筋肉に合った種目を提案します。予定を登録するとその時間にメールで知らせ、メールから記録フォームへすぐ移動できます。

## 主な機能

| 機能 | 内容 |
|---|---|
| 筋トレ記録 | 実行日時・メニュー・重量・回数・セット数・使った筋肉・休憩日数を記録する。種目を選ぶと、メニュー名・筋肉・休憩日数が自動で入る |
| 休息状況 | 記録から「休ませる筋肉（回復予定日時つき）」と「使える筋肉」を表示する |
| おすすめ種目 | 使える筋肉を鍛える種目を提案する |
| 休息中の確認 | 休息中の筋肉を選んで保存しようとすると、筋肉名と回復予定日時の確認が出る。「続ける」を押したときだけ保存される |
| 自己ベスト | 種目ごとの自己ベスト（重量 → 回数 → セット数の順で比べる）を表示する |
| 最高重量の推移 | 種目を選ぶと、日ごとの最高重量を折れ線グラフで表示する |
| 記録の検索 | メニュー名・筋肉・期間で絞り込む。ページ送りあり |
| 通知プラン | 筋肉・日時・場所・メニューを登録すると、その日時に1回だけメールで知らせる。予定日時に休息中の筋肉が含まれるプランは登録できない |
| メールからフォームへ | メールのボタンを押すと、プランの内容が入った記録フォームが開く（未ログインならログイン後に戻る） |
| 閲覧許可 | 相手のメールアドレスを指定して、自分の記録を見せる相手を許可・取り消しする。アカウント名以外は、本人と許可された人しか見られない |
| ユーザー一覧 | 自分に閲覧を許可してくれた人の一覧 |
| ランキング | 自分と、自分に閲覧を許可してくれた人の、種目ごとの自己ベスト重量のランキング（自重は除く） |

## 画面

ホームページ以外はログインが必要です。

| 画面 | URL | 内容 |
|---|---|---|
| ダッシュボード | `/dashboard` | 全身の筋肉図。筋肉名を押すと、その筋肉の種目が入ったフォームが開く |
| マイページ | `/mypage` | 自分のユーザーページ。自己ベスト・推移グラフ・休息状況・おすすめ・記録フォーム・記録一覧。各欄は見出しを押すと開閉する |
| ユーザーページ | `/users/{id}` | 他の人の記録（許可がなければアカウント名のみ） |
| 記録フォーム | `/workouts/create` | `?plan=` でプランの内容、`?muscle=` で筋肉の種目が入った状態で開く |
| 通知プラン | `/plans` | プランの一覧・作成・編集・オンオフ・削除 |
| 閲覧許可 | `/permissions` | 見せる相手の管理と、見せてくれている人の一覧 |
| ユーザー一覧 | `/users` | 自分に閲覧を許可してくれた人 |
| ランキング | `/ranking` | 種目ごとの重量ランキング |
| 設定 | `/settings/*` | プロフィール・パスワード・外観 |

## 技術スタック

- Laravel 12（Livewire スターターキット、認証は Laravel Fortify）
- Livewire 4 / Volt / Flux
- MySQL 8.4（開発環境は Laravel Sail = Docker）
- メール：Laravel の Notification（開発環境ではログに出力）
- グラフ：Chart.js
- タイムゾーン：`Asia/Tokyo`

正確なバージョンは `composer.json` と `package.json` を見てください。

## 開発環境の起動

Docker が必要です。コマンドはリポジトリのフォルダで実行します。

### 初回だけ

```bash
# 1. クローン
git clone https://github.com/yosshi-0999/Kintore_now.git
cd Kintore_now

# 2. PHP のパッケージを入れる（PHP がなくても Docker で実行できる）
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 3. 環境ファイルを作る
cp .env.example .env

# 4. コンテナを起動して初期設定
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed   # テーブル作成と、筋肉・種目・テストユーザーの投入

# 5. フロントエンドのパッケージを入れる
./vendor/bin/sail npm install
```

`npm install` でエラーが出たときは、`./vendor/bin/sail npm install-scripts approve esbuild` を実行してから、もう一度 `npm install` してください。古いコンテナが残っていると起動に失敗することがあります。

### 毎回

```bash
./vendor/bin/sail up -d       # コンテナ起動
./vendor/bin/sail npm run dev # 画面の CSS / JS を読み込むため、起動したままにする
```

| 開くもの | URL |
|---|---|
| アプリ | http://localhost |
| phpMyAdmin（DB の中身を見る） | http://localhost:8080 |

テストユーザーでログインできます：`test@example.com` / `password`

止めるときは `./vendor/bin/sail stop` を実行します。

`sail` と毎回打つのが面倒なら、`alias sail='./vendor/bin/sail'` を設定しておくと便利です。以下は `sail` で書きます。

### pull したあと

他の人の変更を取り込んだら、必要に応じて次を実行します。

```bash
sail composer install   # composer.json が変わったとき
sail npm install        # package.json が変わったとき
sail artisan migrate    # マイグレーションが増えたとき
```

## メール通知の確認

開発環境ではメールを送らず、`storage/logs/laravel.log` に書き出します。

1. 通知プランを1〜2分後の日時で作る
2. 送信コマンドを実行する

   ```bash
   sail artisan reminders:send
   ```

3. `storage/logs/laravel.log` にメール本文が出るので、中のリンクを開く

本番では `routes/console.php` のスケジューラが `reminders:send` を毎分実行します。手元で自動実行を試すなら `sail artisan schedule:work` を起動したままにします。

`.env.example` には Gmail 用の設定も書いてありますが、後ろにある `MAIL_MAILER=log` などの設定が優先されるため、そのままではログ出力になります。

## よく使うコマンド

| 目的 | コマンド |
|---|---|
| テスト | `sail artisan test` |
| 整形（Laravel Pint） | `sail bin pint` |
| マイグレーション | `sail artisan migrate` |
| 初期データ投入 | `sail artisan db:seed` |
| リマインド送信 | `sail artisan reminders:send` |
| スケジューラ（ローカル） | `sail artisan schedule:work` |
| 画面のビルド | `sail npm run build` |

テストは MySQL の `testing` データベースを使うので、`sail up -d` で起動した状態で実行してください。

## フォルダの見どころ

| 場所 | 内容 |
|---|---|
| `app/Services/` | 判定・計算のロジック（休息判定 `MuscleRestService`、自己ベスト、推移グラフ、ランキング） |
| `app/Policies/` | 閲覧制御（他の人の記録を見てよいか） |
| `app/Console/Commands/SendReminders.php` | `reminders:send` コマンド |
| `app/Notifications/WorkoutPlanReminder.php` | 通知メールの本文 |
| `resources/views/livewire/` | 各画面（Volt コンポーネント） |
| `database/seeders/` | 筋肉と種目の初期データ |
| `tests/Feature/` | テスト |

## ドキュメント

| ファイル | 内容 |
|---|---|
| [AGENTS.md](AGENTS.md) | 開発ルール（人も AI も従う）。機能の仕様、DB・コーディングのルール、ブランチ運用 |
| [docs/spec.md](docs/spec.md) | 仕様書（機能要件・ページ構成） |
| [docs/database-design.md](docs/database-design.md) | 現行のデータベース設計。DB に関する判断はこれが正 |
| [docs/how_to_start.md](docs/how_to_start.md) | 開発環境構築のメモ |
| [docs/next_commit.md](docs/next_commit.md) | 次にやること。作業前に読む |
| [docs/log.md](docs/log.md) | 変更の記録（新しいものが上） |

## 開発の進め方（要点）

詳しくは [AGENTS.md](AGENTS.md) を見てください。

- 作業は issue ごとにブランチを切る（例：`feature/12-workout-form`）。PR は `develop` に向けて出し、説明に `Closes #番号` を書く
- コミットの前に `docs/log.md` と `docs/next_commit.md` を更新し、同じコミットに入れる
- `docs/database-design.md` にないテーブル・カラムは勝手に足さない。マージ済みのマイグレーションは編集しない
- 新しいパッケージの追加はチームで相談してから
- `.env` はコミットしない。環境変数を足したら `.env.example` にも書く
