# 変更ログ

このプロジェクトで行った変更を、新しいものが上になるように記録する。
**コミットする前に、そのコミットに含まれる変更をここに追記すること**（ルールは `AGENTS.md` を参照）。

書き方：

```
## YYYY-MM-DD タイトル
### 変更内容
- 何をしたか（理由つき）
### 変更ファイル
- path/to/file
### 確認したこと
- 実行したコマンドと結果（未実行なら「未実行」）
### 注意点
- あれば
```

---

## 2026-10-24 種目ごとの最高重量の推移グラフ（#21）

### 変更内容
- ユーザーページに「最高重量の推移」欄を追加。種目を選ぶと、その種目の日ごとの最高重量を折れ線グラフで表示する。記録と同じく、本人と閲覧を許可されたユーザーだけが見られる
- 対象は自己ベストと同じく種目が紐付いた記録。重量のない（自重）記録は除く。集計は `WeightProgressService` に分けた
- グラフ描画のため `chart.js` を追加（合意済み）。最新の 4.6.1 は公開から2日しかたっておらず、4.6.0 を飛ばして1年ぶりに出た版だったため避け、4.5.1 に固定した（`^` なし）
- Chart.js は `import()` で、グラフを表示するときだけ読み込む（全ページで 147KB 読み込むのを避けるため。`app.js` 本体は 2KB）
- Livewire が再描画してもグラフを作り直さないよう `wire:ignore` を付け、データが変わったときだけ `wire:key` の変化で作り直す
- 欄は最初は閉じておく（#19 で、最初に開くのは自己ベストだけと決めたため）

### 変更ファイル
- `app/Services/WeightProgressService.php`（新規）
- `resources/js/app.js`
- `resources/views/livewire/users/show.blade.php`
- `package.json`、`package-lock.json`
- `tests/Feature/WeightProgressServiceTest.php`（新規）、`tests/Feature/UserPageTest.php`
- `AGENTS.md`（技術スタックに Chart.js を追記）、`docs/spec.md`

### 確認したこと
- `sail artisan test`：113 passed
- `sail npm run build`：成功
- ブラウザでのグラフ表示：未確認（ヘッドレスブラウザがないため）

### 注意点
- pull 後に `sail npm install` が必要
- 値は「日ごとの最高重量」。これまでの自己ベストの推移（階段状に上がるグラフ）にしたい場合は `WeightProgressService` の集計を変える
- 横軸は日付を等間隔で並べている（日付の間隔は反映しない）

---

## 2026-10-24 ユーザーページの各欄を開閉式にする（#19）

### 変更内容
- ユーザーページ（マイページ）の各欄（自己ベスト・休ませる筋肉・使える筋肉・おすすめの種目・筋トレ内容を記録・記録）を、見出しを押すと中身が開く形にした
- 最初に開いているのは自己ベストだけ（合意済み）。ただし検索条件付きの URL や2ページ目以降を開いたときは、結果が見えるように記録欄も開く
- 開閉は HTML の `<details>` で行い、JavaScript を足していない。部品は `x-collapsible-section` にまとめた
- Livewire が再描画したときに開閉状態が戻らないよう、`<details>` に `wire:ignore.self` を付けた（中身は通常どおり更新される）

### 変更ファイル
- `resources/views/components/collapsible-section.blade.php`（新規）
- `resources/views/livewire/users/show.blade.php`
- `tests/Feature/UserPageTest.php`（テスト2件追加）
- `docs/spec.md`

### 確認したこと
- `sail artisan test`：108 passed
- ブラウザでの見た目・開閉の動作：未確認

### 注意点
- 開閉状態は保存しない（ページを開き直すと初期状態に戻る）

---

## 2026-10-24 ユーザー一覧を閲覧許可のあるユーザーだけにする（#20）

### 変更内容
- ユーザー一覧に全ユーザーを出していたのを、自分に閲覧を許可しているユーザー（`view_permissions` の `viewer_id` が自分の行の `owner`）だけにした。記録を見られる範囲（`UserPolicy::viewRecords`）と同じになる
- 該当者がいないときの文言を追加

### 変更ファイル
- `resources/views/livewire/users/index.blade.php`
- `tests/Feature/UserPageTest.php`（テスト2件追加）、`tests/Feature/PagesTest.php`（一覧に自分の名前が出なくなったため確認内容を変更）
- `docs/spec.md`

### 確認したこと
- `sail artisan test`：106 passed

### 注意点
- ユーザーページ（`/users/{id}`）を URL で直接開けば、許可がなくてもアカウント名は見える（アカウント名は常に公開のルールどおり）

---

## 2026-10-24 ブログ機能を削除

### 変更内容
- ブログ記事（F11）の処理を削除（依頼による）。2026-10-17 の「ブログ記事追加」で入ったもののうち、ブログ専用のファイル・ルート・サイドバーのリンクを消した
- 同じコミットに含まれていた日本語化（`lang/ja`、`phpunit.xml` の `APP_LOCALE` など）と `composer.json` の Livewire 指定は、ブログと関係がないので残した
- `docs/spec.md` のブログに関する記述を作成前の状態に戻した（F11 は任意機能・未作成として残す）

### 変更ファイル
- 削除：`app/Services/BlogPost.php`、`app/Services/BlogPostService.php`、`resources/views/livewire/blog/index.blade.php`、`resources/views/livewire/blog/show.blade.php`、`resources/posts/*.md`（3本）、`tests/Feature/BlogTest.php`
- 編集：`routes/web.php`、`resources/views/components/layouts/app/sidebar.blade.php`、`docs/spec.md`

### 確認したこと
- `sail artisan test`：104 passed
- `./vendor/bin/pint --test routes/web.php`：passed
- `php artisan route:list` に blog のルートがないことを確認

### 注意点
- 既存の記事（Markdown 3本）も削除した。必要になったら git の履歴（2ba2ecd）から戻せる

---



---

## 2026-10-15 GitHub Actions のテストを MySQL で動かす

### 変更内容
- PR でテストが落ちていた。原因は、テスト用 DB を MySQL に統一した（`phpunit.xml` の `DB_CONNECTION=mysql`）のに、`tests.yml` が SQLite 前提で MySQL サーバーを起動していなかったこと（`.env.example` の `DB_HOST=mysql` に接続できない）
- `tests.yml` に MySQL 8.4 の `services` を追加し（Sail と同じバージョン）、ジョブの `env` で接続先（`127.0.0.1` / `testing` / root）を指定。ジョブの環境変数は `.env` より優先される
- 不要になった SQLite ファイルの作成ステップを削除

### 変更ファイル
- `.github/workflows/tests.yml`

### 確認したこと
- YAML として読み込めることを確認
- `composer.lock` に PHP 8.4（CI のバージョン）で入らないパッケージがないことを確認
- GitHub Actions 上での実行：**未確認**（push 後に PR のチェック結果を見る）

### 注意点
- 手元（Sail）は PHP 8.5、CI は PHP 8.4。バージョン差による失敗に注意

---

## 2026-10-15 作業記録の運用を追加

### 変更内容
- `docs/log.md`（変更の記録）と `docs/next_commit.md`（次にやること）を作成
- `AGENTS.md` に、次の作業は `docs/next_commit.md` を参考に決めること、コミット前に2つのファイルを更新することを追記

### 変更ファイル
- `docs/log.md`（新規）、`docs/next_commit.md`（新規）、`AGENTS.md`

### 確認したこと
- ドキュメントのみの変更のため `php artisan test` は未実行（直前の実行は 58 passed）

### 注意点
- `package.json`（先頭の空行）と `package-lock.json` の差分は出どころ不明のため、このコミットに含めていない

---

## 2026-10-15 プロトタイプ実装（必須機能 F1〜F8）

### 変更内容
- **データの土台**：`docs/database-design.md` に沿ってマイグレーション7つを作成（muscles → exercises → workouts → muscle_workout → view_permissions → workout_plans → muscle_workout_plan）。外部キーはすべて `cascadeOnDelete` を明示
- `muscles.name` / `exercises.name` にユニーク制約、`workouts(user_id, performed_at)` と `workout_plans(is_enabled, sent_at, scheduled_at)` にインデックスを追加（カラムの追加はなし）
- モデル（`fillable` 明示）、Factory、Seeder を作成。筋肉11件・種目15件の初期データは**仮**
- **休息判定**：`App\Services\MuscleRestService` に集約。基準日時を渡せるので、記録フォーム（現在時刻）と通知プラン（予定日時）で同じロジックを使う。休息中の筋肉・使える筋肉・おすすめ種目を返す
- **F1 / F4 記録フォーム**（`workouts.form`）：休息中の筋肉が含まれると、筋肉名と回復予定日時を確認表示。「続ける」のときだけ保存。確認状態は `#[Locked]` プロパティで持ち、クライアントから確認を飛ばせないようにした。種目を選ぶとメニュー・筋肉・休憩日数が初期値として入る
- **F2 / F3 / F8 ユーザーページ**（`users.show`、マイページ兼用）：休ませる筋肉、使える筋肉、おすすめ種目、最近の記録。本人のみ入力フォームを表示。他人は `UserPolicy::viewRecords` を通し、許可がなければ名前のみ
- **F5 通知プラン**（`plans.index` / `plans.edit`）：一覧・作成・編集・オンオフ・削除。予定日時の時点で休息中の筋肉が含まれるとエラー。予定日時を変えたら `sent_at` をリセット
- **F6 / F7 メール**：`WorkoutPlanReminder`（`MailMessage`）と `reminders:send` コマンド。スケジューラで毎分実行。送信**前**に `sent_at` を記録して二重送信を防ぎ、失敗時は戻す。リンクは `route('workouts.create', ['plan' => id])` のみ。未ログイン時はログイン後に元のページへ戻る（スターターキットの `redirectIntended`）。他人のプランは `WorkoutPlanPolicy` で 403
- **閲覧許可**（`permissions.index`）：メールアドレスで相手を指定して許可・取り消し。自分に見せてくれている人の一覧も表示
- サイドバーに「筋トレ」グループ（マイページ、記録する、通知プラン、ユーザー一覧、閲覧許可）を追加

### 変更ファイル
- 新規：`app/Services/MuscleRestService.php`、`app/Actions/RecordWorkout.php`、`app/Actions/SaveWorkoutPlan.php`、`app/Policies/UserPolicy.php`、`app/Policies/WorkoutPlanPolicy.php`、`app/Notifications/WorkoutPlanReminder.php`、`app/Console/Commands/SendReminders.php`
- 新規：`app/Models/{Muscle,Exercise,Workout,ViewPermission,WorkoutPlan}.php`
- 新規：`database/migrations/2026_10_15_00000{1..7}_*.php`、`database/factories/{Muscle,Exercise,Workout,WorkoutPlan}Factory.php`、`database/seeders/{Muscle,Exercise}Seeder.php`
- 新規：`resources/views/livewire/{workouts,users,plans,permissions}/*.blade.php`
- 新規：`tests/Feature/{MuscleRestService,WorkoutForm,WorkoutPlan,SendReminders,UserPage,Pages}Test.php`
- 変更：`app/Models/User.php`、`routes/web.php`、`routes/console.php`、`database/seeders/DatabaseSeeder.php`、`resources/views/components/layouts/app/sidebar.blade.php`

### 確認したこと
- `sail artisan migrate` / `sail artisan db:seed`：成功
- `sail artisan test`：58 passed（168 assertions）。AGENTS.md 必須の3テスト（確認が出る／確認後に保存／休息中でなければ確認なしで保存）を含む
- `sail pint`：整形済み
- ブラウザでの目視確認：**未実行**

### 注意点
- バリデーションメッセージは各コンポーネント内に日本語で記述。アプリ全体の日本語化（`lang/ja`）はしていない
- 本番では `schedule:run` を毎分動かす cron が必要（ローカルは `sail artisan schedule:work`）

---

## 2026-10-15 テスト環境の整備

### 変更内容
- テスト用 DB を MySQL（`testing` データベース）に統一。`phpunit.xml` に `DB_CONNECTION=mysql` を明示
- `ProfileUpdateTest` の失敗（`No hint path defined for [layouts]`）を修正。インストールされている Livewire が v4 で、デフォルトレイアウトが `layouts::app` に変わっていたため、`config/livewire.php` で `components.layouts.app`（スターターキットの場所）を指定
- PHP 8.5 で非推奨の `PDO::MYSQL_ATTR_SSL_CA` を、PHP 8.5 以上では `Pdo\Mysql::ATTR_SSL_CA` を使うよう分岐（`composer.json` の PHP ^8.2 を維持）

### 変更ファイル
- `config/livewire.php`（新規）、`phpunit.xml`、`config/database.php`

### 確認したこと
- `sail artisan test`：27 passed（失敗・deprecated なし）

### 注意点
- Livewire のバージョンは `composer.json` で固定されておらず、Volt 経由で v4 が入っている。スターターキットのドキュメント（v3 前提）と差がある

---

## 2026-10-15 開発環境の設定と決定事項の反映

### 変更内容
- 決定事項をドキュメントに反映
  - 通知プランは専用テーブル（`workout_plans` / `muscle_workout_plan`）。`users` へのカラム追加はやめた
  - 通知は日付指定の1回きり → `scheduled_at`（dateTime）と `sent_at`（NULL なら未送信）
  - 予定日時の時点で休息中の筋肉が含まれるプランは作成・更新できない
  - 場所は自由入力
  - タイムゾーンは `Asia/Tokyo`
  - 開発用 DB は MySQL（Sail）
- `config/app.php` の `timezone` を `Asia/Tokyo` に変更
- `.env.example` を MySQL（Sail の標準値）に変更

### 変更ファイル
- `AGENTS.md`、`docs/database-design.md`、`docs/spec.md`、`config/app.php`、`.env.example`

### 確認したこと
- `now()` が日本時間を返すことを確認

### 注意点（ファイル変更なしの環境作業）
- `npm run dev` は Sail コンテナ内で実行する（`./vendor/bin/sail npm run dev`）。ホストで実行すると 5173 番ポートが重なる
- MySQL のボリュームが旧設定（root／パスワードなし）で初期化されていたため、`sail down -v` で作り直した（データは空だった）。`.env` の DB ユーザーやパスワードを変えた場合は、同様に作り直しが必要（データは消える）
- `APP_KEY` が空だったため、`sail artisan key:generate` を実行してもらった
