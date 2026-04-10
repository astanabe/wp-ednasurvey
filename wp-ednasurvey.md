# 環境DNA市民調査報告サイトプラグイン (wp-ednasurvey)

WordPressにプラグインとしてインストールすることで、環境DNA市民調査のデータ収集・管理機能を提供するプラグイン。日本語と英語に対応し、ブラウザの`Accept-Language`ヘッダーにより自動切り替えを行う。

- プラグインバージョン: 1.7.0
- データベーススキーマバージョン: 1.5.0

## 動作要件

| 項目 | 要件 |
|------|------|
| WordPress | 6.4以上 |
| テーマ | [GeneratePress](https://generatepress.com/) |
| PHP | 8.1以上 |
| PHP拡張(必須) | zip (PhpSpreadsheet用) |
| PHP拡張(推奨) | exif (JPEG GPS取得), mbstring (日本語処理) |
| CLIツール(推奨) | heif-dec/heif-convert (HEIC→JPEG変換), ffmpeg (同), exiftool (PHP exifがない場合のGPS取得フォールバック) |
| PHP拡張(任意) | imagick (HEIC→JPEG変換。なくてもCLIツールで代替可) |
| Composerパッケージ | phpoffice/phpspreadsheet (Excel生成・読み込み) |

Settingsページ最上端の「サーバー環境」セクションで、各コンポーネントのインストール状況・バージョン・HEIC対応状況が確認できる。

## データ保存方式

調査データは専用のカスタムテーブル（5テーブル）に保存する。WordPress標準のカスタム投稿タイプは使用しない。写真は`wp-content/uploads/ednasurvey/{user_id}/{site_id}/`に保存し、WordPress Media Libraryは使用しない。

オフライン送信時の写真は一時ディレクトリ`wp-content/uploads/ednasurvey/temp/{session_id}/`に保存され、送信確定時に永続ディレクトリに移動される。一時ディレクトリはWordPress cronで24時間経過後に自動削除。

### インストール・アンインストール動作

- **新規インストール時**: `ednasurvey_db_version`オプションが存在しない場合、既存テーブルがあればDROPしてからCREATEする（クリーンな初期状態を保証）
- **デアクティベート時**: プラグイン一覧画面でDeactivateクリック時に確認ダイアログを表示し、「アンインストール時にデータを削除するか」を問い合わせる
- **アンインストール時**: デアクティベート時に「削除する」を選択した場合のみ、テーブル・写真・設定を全削除する。「保持する」を選択した場合はデータはそのまま残る

## URLルーティング

WordPressのリライトルールではなく、`parse_request`フックで`REQUEST_URI`を直接解析する方式を採用。ユーザー名がWordPressの実在ユーザーと一致する場合のみインターセプトする。WordPressの内部404フラグをリセットし、`status_header(200)`を設定する。HTMLの`<title>`と`<h1>`は`EdnaSurvey_Router::get_page_titles()`で一元管理。

| URL | ページ |
|-----|--------|
| `/{USERNAME}/` | ダッシュボード |
| `/{USERNAME}/onlinesubmission` | オンラインデータ入力 |
| `/{USERNAME}/offlinetemplate` | オフラインテンプレートダウンロード |
| `/{USERNAME}/offlinesubmission` | オフラインデータアップロード |
| `/{USERNAME}/sites` | 報告地点一覧 |
| `/{USERNAME}/map` | 報告地点マップ |
| `/{USERNAME}/chat` | 管理者とのチャット |
| `/{USERNAME}/site/{internal_sample_id}` | 地点詳細 |

### アクセス制御

- 全ページでログイン必須（未ログインはwp-loginにリダイレクト）
- 本人または管理者のみアクセス可能
- Subscriberはwp-adminへのアクセスをブロックされ、`/{USERNAME}/`にリダイレクトされる
- Subscriberの管理バーは非表示
- ログイン後、Subscriberは自動的に`/{USERNAME}/`にリダイレクトされる
- ログアウト後はwp-loginページにリダイレクト

## ユーザー向けページ

### ダッシュボード (`/{USERNAME}/`)

ログインユーザーのトップページ。ウェルカムメッセージ、ログアウトボタン、および以下6つのナビゲーションカードを表示する:

1. オンラインデータ入力
2. オフラインテンプレートダウンロード
3. オフラインデータアップロード
4. 報告地点一覧
5. 報告地点マップ
6. 管理者とのチャット

### オンラインデータ入力ページ (`/{USERNAME}/onlinesubmission`)

1地点分の調査データをオンラインで入力するページ。

#### 入力フォーム

Settingsで有効化された全データ項目の入力フォームを表示する。`?copy_from={internal_sample_id}`パラメータ付きでアクセスすると、既存地点のデータが事前入力される（入力ミス修正用の再投稿機能）。再投稿時にはinternal_sample_idは新規に生成される。

#### 位置情報の入力

- Leaflet地図を表示し、Geolocation APIで現在地を初期表示
- 地図クリックでピンを設置（ドラッグ移動可能）
- 緯度経度（小数点6桁）が自動的に隠しフィールドに設定される

#### 写真のアップロード

- JPEG、HEIF/HEICに対応
- HEIF/HEICはサーバー側でJPEGに変換。変換優先順位: Imagick PHP拡張 → heif-dec/heif-convert CLI → ImageMagick CLI → FFmpeg CLI
- JPEG変換後にEXIF GPSデータを取得（HEIC→JPEG変換時にEXIFは保持される）。取得方法: PHP exif拡張 → exiftool CLI
- アップロード枚数上限はSettingsで設定可能

#### 送信処理

- AJAXで送信（ページリロードなし）
- 成功時: フォームを非表示にし、成功メッセージとダッシュボードへのリンクを表示
- 失敗時: エラーメッセージを表示し、入力データを保持

#### 送信時に自動記録されるメタデータ

ユーザーには表示されず、管理者のみが閲覧可能な以下のデータが各サンプルに自動記録される:

| フィールド | 内容 |
|-----------|------|
| internal_sample_id | ユニーク内部ID（`{user_login}-{IPゼロフィル}-{YYYYMMDDhhmmss}-{ランダム16hex}`）。全てのURL・リンクでサンプルを識別する唯一のID |
| submitted_user_login | ログインユーザー名 |
| submitted_user_email | ログインユーザーE-mail |
| submitted_user_name | ログインユーザー姓名（`firstname lastname`） |
| submitted_ip | 送信元IPアドレス（IPv4/IPv6対応、プロキシヘッダー考慮） |
| submitted_hostname | 送信元の逆引きDNSホスト名 |
| submitted_geo | IPから推定した地域（国, 地域, 都市）。PHP geoip拡張またはip-api.com(タイムアウト2秒)で解決 |
| submitted_at | 送信日時（採集日時とは別） |
| submitted_user_agent | ブラウザ/デバイス情報（最大500文字） |
| submitted_method | 送信方法（`online` または `offline`） |

### オフラインテンプレートダウンロードページ (`/{USERNAME}/offlinetemplate`)

電波の届かない現地調査用のExcelテンプレート(.xlsx)をダウンロードするページ。ファイル名は`ednasurvey_{user_login}.xlsx`。日本語環境ではフォント「游ゴシック」を使用。

#### テンプレートの構造

| 行 | 内容 | 配色 | 編集可否 |
|----|------|------|----------|
| 1行目 | DB列名（ラベル行） | 濃紺背景・白文字 | 編集禁止 |
| 2行目 | 言語対応ラベル（JA/EN自動切替）| 薄黄背景・太字 | 編集禁止 |
| 3行目 | 必須/省略可（条件付き省略可含む） | 薄黄背景 | 編集禁止 |
| 4行目 | 入力形式の説明 | 薄黄背景 | 編集禁止 |
| 5行目 | 入力例 | 薄黄背景 | 編集禁止 |
| 6行目以降 | データ入力欄 | 白 | 入力可能 |

- 1〜2行目はペイン固定（スクロール時も常に表示）
- 1〜5行目はシート保護で編集禁止、6行目以降は入力可能
- ブック保護でシートの追加・削除を禁止
- データバリデーション:
  - 日付: YYYY-MM-DD形式のテキスト強制
  - 時刻: hh:mm形式のテキスト強制
  - 緯度: -90〜90の小数（6桁表示）。分秒表記禁止。南緯は負の値
  - 経度: -180〜180の小数（6桁表示）。分秒表記禁止。西経は負の値
  - 濾過水量: 整数値
  - セレクト型カスタムフィールド: ドロップダウンリスト
- numberカラムは含まない（インポート時に自動採番）

#### 必須項目

- sample_id, survey_date, survey_time, sitename_local, sitename_en, correspondence, collector1: 必須
- watervol1, watervol2: 必須（0可）
- latitude, longitude: 写真にGPSデータがある場合は省略可
- photo_files: 写真を撮影しなかった場合は省略可
- collector2〜5, notes: 省略可

#### ページ上の注意事項

- 一般的な記入手順
- 写真ファイル名を空欄にするとEXIF撮影日時で自動マッチングされる旨
- Androidでのファイル名の注意（ファイルマネージャーとブラウザで異なる場合がある）
- スマートフォンでの使用方法（Googleスプレッドシート、Microsoft Excel）

### オフラインデータアップロードページ (`/{USERNAME}/offlinesubmission`)

4ステップの送信フロー:

**Step 0**: 今回アップロードする地点数を入力。写真上限 = 地点数 × photo_upload_limit。

**Step 1**: 写真アップロード。ネイティブのファイル選択ボタンで複数ファイルを一括選択可能。アップロードするとサーバーの一時ディレクトリに保存され、EXIF解析結果（撮影日時・GPS座標）がサムネイルとともにリスト表示される。各写真に削除ボタン。写真0枚でもStep 2に進行可能（撮影忘れに対応）。

**Step 2**: Excelファイル(.xlsx)をアップロード。サーバー側で以下の分析を実行:
- Excelパース（6行目以降をデータとして読み込み）
- 必須項目バリデーション
- 写真マッチング:
  1. photo_files記述あり → ファイル名完全一致で照合
  2. photo_files空欄 → EXIF撮影日時とsurvey_date+survey_time ±閾値（Settings: photo_time_threshold、デフォルト30分）でマッチング
  3. 1写真が複数サンプルに該当 → エラー + 候補表示
- 位置情報補完: サイトのlatitude/longitudeが空欄でマッチした写真にGPSがある場合、photo_files先頭 → survey_time最近接の順で補完
- エラーがあればStep 1に戻りエラー表示。写真追加やExcel修正後にStep 2を再実行可能

**Step 3**: 地図確認。Leaflet地図上にマーカーを表示（ドラッグ移動可能）。位置情報のない地点は地図クリックで設置。写真なし地点には警告バッジを表示。全地点の位置確認後、一括送信。

### 地点詳細ページ (`/{USERNAME}/site/{internal_sample_id}`)

個別の地点データを全項目表示するページ。写真がある場合はサムネイルギャラリーを表示（クリックで原寸表示）。管理者でアクセスした場合は送信メタデータ（Internal Sample ID、送信日時、IP/Geo等）も表示。

ボタン: 「修正して再投稿」「地点一覧に戻る」「マップに戻る」

### 報告地点一覧ページ (`/{USERNAME}/sites`)

カードベースのレイアウトで自分の投稿地点を一覧表示する。

各カードに表示される内容:
- 連番
- 写真サムネイル（最大3枚 + 残数表示）
- 地点名（言語に応じてsitename_localまたはsitename_en）
- 調査日時（秒なし）
- サンプルID
- 緯度経度
- 「詳細」ボタン（地点詳細ページへ）
- 「修正して再投稿」ボタン（`/onlinesubmission?copy_from={internal_sample_id}`に遷移）

### 報告地点マップページ (`/{USERNAME}/map`)

Leaflet地図（高さ600px）上に自分の全地点をマーカーで表示。マーカーをクリックするとポップアップに以下を表示:
- 地点名（太字）
- 調査日、時刻、サンプルID
- 「詳細」ボタン（地点詳細ページへ）
- 「修正して再投稿」ボタン

全マーカーが収まるように地図のバウンドを自動調整。

### 管理者とのチャットページ (`/{USERNAME}/chat`)

ユーザーと管理者の1対1チャット。

- タイムライン形式表示
- 15秒間隔のポーリングで新着メッセージを取得（REST API: `/wp-json/ednasurvey/v1/messages/{user_id}`）
- ユーザーが書き込むと管理者にメール通知（`wp_mail()`）
- 各メッセージに投稿者名・日時を表示

### データの編集・削除ルール

- 参加者は投稿済みデータを直接編集・削除できない
- 入力ミスは地点一覧・地図ページ・地点詳細ページの「修正して再投稿」ボタンから既存データをコピーして修正・再投稿する
- 古いデータの削除は管理者にチャットで依頼し、管理者がAll Sitesから一括削除する

## 管理者ダッシュボード

管理者ダッシュボードに「eDNA Survey」メニューを追加し、以下のページを提供する。

### Download Data（デフォルトページ）

Subscriberの地点情報をCSV/TSVファイルまたは画像ファイルURLリストとしてダウンロードする。

#### フィルタリング

ユーザー、日付範囲で絞り込み可能。

#### ダウンロード形式

- **CSV/TSV**: UTF-8 BOM付き。メタデータ列を含む（後述）
- **画像URLリスト**: テキストファイル（1行1URL）

#### CSV/TSVファイルのヘッダー順序

```
number,internal_sample_id,submitted_user_login,submitted_user_email,submitted_user_name,submitted_ip,submitted_hostname,submitted_geo,submitted_at,submitted_user_agent,submitted_method,sample_id,survey_date,survey_time,latitude,longitude,sitename_local,sitename_en,correspondence,collector1,collector2,collector3,collector4,collector5,watervol1,watervol2,env_broad,env_local1,...,env_local7,weather,wind,custom_{field_key}...,notes,photo_files
```

- 先頭にnumber（連番）、続いて送信メタデータ11列（管理者用）
- その後にSettingsで有効化されたデータ項目
- カスタムフィールドは`custom_{field_key}`
- 末尾にnotes、photo_files
- Settingsで無効化された項目は出力されない

### Add Users

CSV/TSVファイルをアップロードして多数のユーザーをSubscriberとして一括追加する。Welcomeメールは送信しない（別プラグインで対応）。

#### ユーザー情報CSV/TSVファイルの形式

```
email,firstname,lastname
```

- `user_login`は`user_email`と同一とする
- 既存ユーザーと同じメールアドレスの場合はスキップ
- インポート完了後、作成数・スキップ数を表示

### All Sites

全ユーザーの全地点データを管理する`WP_List_Table`ベースのテーブル。

- **Screen Options**: 表示件数（デフォルト50）、カラム表示/非表示の切替
- **必須カラム**（非表示不可）: Internal Sample ID（primaryカラム、行ホバーでDetailリンク表示）
- **オプションカラム**: User, Submitted, Method, IP/Geo, Survey Date, Survey Time, Site Name (Local/EN), Representative, Collector1-5, Sample ID, Watervol1/2, Notes, Lat, Lon, Photos
- **Bulk Actions**: チェックボックスで複数選択 → Delete一括削除
- **ソート**: カラムヘッダークリックで昇順/降順
- **Detail**: `wp-admin/admin.php?page=edna-survey-site-detail&site={internal_sample_id}`（管理画面内ページ）

### Site Detail（管理者用、メニュー非表示）

`wp-admin/admin.php?page=edna-survey-site-detail&site={internal_sample_id}` で表示。ユーザー向け地点詳細とは別のテンプレート。全データ項目＋送信メタデータ（IP, Geo, User Agent等）＋写真ギャラリーを表示。「← All Sites」「← Sites Map」ボタン。

### Sites Map

全ユーザーの全地点をLeaflet地図上に表示。マーカークリックでポップアップ（地点名、日時、ユーザー名、Detailリンク）を表示。中心座標・ズームレベル・タイルサーバーはSettingsで設定。DetailリンクはAll Sites同様の管理画面内ページ。

### Settings

#### サーバー環境チェック（ページ最上端）

以下のコンポーネントの状態を表形式で表示:
- PHP (バージョン)
- Imagick (HEIC対応状況)
- CLIツール: ImageMagick, heif-dec/heif-convert, FFmpeg — それぞれHEIC対応チェック（ImageMagick: `identify -list format`でHEIC確認、heif-dec: `--list-decoders`でlibde265確認、FFmpeg: `-decoders`でhevc確認）
- exiftool (PHP exifのフォールバック)
- exif, mbstring, zip (PHP拡張)
- PhpSpreadsheet (Composerパッケージ)

検出されたツールのみ表示。全HEIC変換手段がない場合はエラー表示。

#### 地図設定

- タイルサーバーURL
- タイルアトリビューション
- デフォルト中心座標（緯度・経度）
- デフォルトズームレベル

#### 写真設定

- 1地点あたりのアップロード枚数上限
- 写真時刻マッチング閾値（分）: オフラインアップロードでphoto_files空欄時にEXIF撮影日時とsurvey_timeを照合する際の許容誤差。デフォルト30分

#### 外部コマンドパス

各CLIツールのフルパスを設定可能。空欄の場合はPATHから自動検出:
- ImageMagick (magick / convert): 自動検出順 magick → convert
- heif-dec / heif-convert (libheif): 自動検出順 heif-dec → heif-convert
- FFmpeg
- exiftool

各フィールドに検出済みパスを表示。

#### デフォルトデータ項目ON/OFF

| 設定キー | 対応項目 |
|----------|----------|
| survey_datetime | 採集日時 |
| location | 緯度経度 |
| site_name | 地点名(現地語/英語) |
| correspondence | 代表者氏名 |
| collectors | 採集者(最大5名) |
| sample_id | サンプルID |
| water_volume | 濾過水量(2レプリケート) |
| env_broad | 環境(大) |
| weather | 天候 |
| wind | 風 |
| notes | 備考 |
| photos | 写真 |

#### カスタムデータ項目

動的に追加・編集・削除可能:
- 項目キー（英数字）
- 項目名（日本語・英語）
- 入力タイプ（text / number / select / date / textarea）
- 必須/任意
- オプション（JSON: choices, min/max等）
- 有効/無効
- 並び順

### Messages

全ユーザーのチャットを一覧表示。各ユーザーのチャットを開いてタイムラインを確認し、管理者として返信可能。

## データベーススキーマ

全テーブルは`{prefix}ednasurvey_`プレフィックスで、`dbDelta()`で作成する。

### ednasurvey_sites

調査地点データおよび送信メタデータ。

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | 主キー（内部DB操作用） |
| user_id | BIGINT UNSIGNED NOT NULL | WordPressユーザーID |
| survey_date | DATE | 採集日 |
| survey_time | TIME | 採集時刻 |
| latitude | DECIMAL(9,6) | 緯度 |
| longitude | DECIMAL(10,6) | 経度 |
| sitename_local | VARCHAR(255) | 現地語地点名 |
| sitename_en | VARCHAR(255) | 英語地点名 |
| correspondence | VARCHAR(255) | 代表者氏名 |
| collector1〜5 | VARCHAR(255) | 採集者1〜5 |
| sample_id | VARCHAR(255) | ユーザー入力サンプルID |
| watervol1 | DECIMAL(10,2) | 濾過水量1(mL) |
| watervol2 | DECIMAL(10,2) | 濾過水量2(mL) |
| env_broad | VARCHAR(255) | 環境(大)（marine/estuarine/mangrove/large river/small river/freshwater lake/brackish lake/saline lake/sterile water） |
| env_local1〜7 | VARCHAR(255) | 環境(小)1〜7。env_broadに従属する選択肢から最大7個選択 |
| weather | VARCHAR(255) | 天候（clear sky/sunny/cloudy/foggy/rain/hail/sleet/snow） |
| wind | VARCHAR(255) | 風（windy/not windy） |
| notes | TEXT | 備考 |
| internal_sample_id | VARCHAR(255) UNIQUE | 内部ユニークID。全URL・リンクで使用 |
| submitted_user_login | VARCHAR(60) | 送信ユーザー名 |
| submitted_user_email | VARCHAR(100) | 送信ユーザーE-mail |
| submitted_user_name | VARCHAR(200) | 送信ユーザー姓名 |
| submitted_ip | VARCHAR(45) | 送信元IP |
| submitted_hostname | VARCHAR(255) | 送信元ホスト名 |
| submitted_geo | VARCHAR(255) | 送信元推定地域 |
| submitted_at | DATETIME | 送信日時 |
| submitted_user_agent | TEXT | ブラウザ/デバイス情報 |
| submitted_method | VARCHAR(20) | 送信方法(online/offline) |
| created_at | DATETIME | レコード作成日時 |
| updated_at | DATETIME | レコード更新日時 |

インデックス: idx_user_id, idx_survey_date, idx_internal_sample_id (UNIQUE)

### ednasurvey_photos

写真メタデータ。

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | 主キー |
| site_id | BIGINT UNSIGNED NOT NULL | 地点ID |
| user_id | BIGINT UNSIGNED NOT NULL | ユーザーID |
| original_filename | VARCHAR(255) NOT NULL | 元ファイル名 |
| stored_filename | VARCHAR(255) NOT NULL | 保存ファイル名 |
| file_path | VARCHAR(512) NOT NULL | 相対パス |
| file_url | VARCHAR(512) NOT NULL | URL |
| mime_type | VARCHAR(50) | MIMEタイプ |
| exif_latitude | DECIMAL(9,6) | EXIF緯度 |
| exif_longitude | DECIMAL(10,6) | EXIF経度 |
| created_at | DATETIME | 作成日時 |

### ednasurvey_custom_fields

カスタムフィールド定義。

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | 主キー |
| field_key | VARCHAR(100) NOT NULL UNIQUE | フィールドキー |
| label_ja | VARCHAR(255) NOT NULL | 日本語ラベル |
| label_en | VARCHAR(255) NOT NULL | 英語ラベル |
| field_type | VARCHAR(50) DEFAULT 'text' | 入力タイプ |
| field_options | TEXT | オプション(JSON) |
| is_required | TINYINT(1) DEFAULT 0 | 必須フラグ |
| sort_order | INT DEFAULT 0 | 並び順 |
| is_active | TINYINT(1) DEFAULT 1 | 有効フラグ |
| created_at | DATETIME | 作成日時 |

### ednasurvey_site_custom_data

カスタムフィールド値（EAVパターン）。

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | 主キー |
| site_id | BIGINT UNSIGNED NOT NULL | 地点ID |
| field_id | BIGINT UNSIGNED NOT NULL | フィールドID |
| field_value | TEXT | 値 |

UNIQUE KEY: (site_id, field_id)

### ednasurvey_messages

チャットメッセージ。

| カラム | 型 | 説明 |
|--------|-----|------|
| id | BIGINT UNSIGNED AUTO_INCREMENT | 主キー |
| conversation_user_id | BIGINT UNSIGNED NOT NULL | 会話対象ユーザーID |
| sender_id | BIGINT UNSIGNED NOT NULL | 送信者ID |
| message | TEXT NOT NULL | メッセージ本文 |
| is_read | TINYINT(1) DEFAULT 0 | 既読フラグ |
| created_at | DATETIME | 送信日時 |

## ユーザー入力データ項目一覧

Settingsで有効/無効を切り替え可能なデフォルト項目:

| DB列名 | 日本語ラベル | 英語ラベル | 型 | 必須 | 備考 |
|--------|-------------|-----------|-----|------|------|
| sample_id | サンプルID | Sample ID | テキスト | 必須 | ユーザー指定文字列 |
| survey_date | 採集日 | Survey Date | 日付 | 必須 | YYYY-MM-DD |
| survey_time | 採集時刻 | Survey Time | 時刻 | 必須 | hh:mm(24時間) |
| latitude | 緯度 | Latitude | 小数 | 条件付き | 小数表記6桁。分秒禁止。南緯は負。写真GPSがあれば省略可 |
| longitude | 経度 | Longitude | 小数 | 条件付き | 小数表記6桁。分秒禁止。西経は負。写真GPSがあれば省略可 |
| sitename_local | 現地語地点名 | Local Language Site Name | テキスト | 必須 | 日本では日本語地点名 |
| sitename_en | 英語地点名 | Site Name (English) | テキスト | 必須 | |
| correspondence | 代表者氏名 | Representative | テキスト | 必須 | |
| collector1 | 採集者1 | Collector 1 | テキスト | 必須 | |
| collector2〜5 | 採集者2〜5 | Collector 2〜5 | テキスト | 省略可 | |
| watervol1 | 濾過水量1(mL) | Filtered Water Vol. 1 (mL) | 整数 | 必須(0可) | mL単位、1mL精度 |
| watervol2 | 濾過水量2(mL) | Filtered Water Vol. 2 (mL) | 整数 | 必須(0可) | mL単位、1mL精度 |
| env_broad | 環境(大) | Environment (Broad) | セレクト | 必須 | marine/estuarine/mangrove/large river/small river/freshwater lake/brackish lake/saline lake/sterile water。日本語表示: 海/河川感潮域/マングローブ/大河川下流部/小河川や大河川上流部/淡水湖/汽水湖/塩湖/滅菌水。estuarine: 河口から外は含まない。large river: 遊覧船が運行できるか（急流下り船は含まない）。saline lake: 汽水湖や潟湖は含まない。sterile water: ブランク・ネガティブコントロール用 |
| env_local1〜7 | 環境(小)1〜7 | Env. (Local) 1〜7 | セレクト | env_local1は必須、2〜7は省略可 | env_broadの選択に従属する選択肢から最大7個。Excelでは7カラムにINDIRECT従属ドロップダウン |
| weather | 天候 | Weather | セレクト | 必須 | clear sky/sunny/cloudy/foggy/rain/hail/sleet/snow。日本語表示: 快晴/晴れ/曇り/霧/雨/霰や雹/みぞれ/雪 |
| wind | 風 | Wind | セレクト | 必須 | windy/not windy。日本語表示: 強風/無風～弱風。判定基準: 濾過に使用するシリンジまたはフィルターホルダーが風で動いていくかどうか |
| notes | 備考 | Notes | テキスト | 省略可 | 自由記述 |
| photo_files | 写真ファイル名 | Photo Filenames | テキスト | 条件付き | カンマ区切り。写真未撮影なら省略可 |

カスタム項目はSettingsから追加可能で、DB上は`ednasurvey_custom_fields`（定義）+ `ednasurvey_site_custom_data`（値）のEAVパターンで管理。Excel/CSVでは`custom_{field_key}`列として出力される。

## セキュリティ

- 全フォームにWordPress nonce
- 全AJAXリクエストで`check_ajax_referer()`によるnonce検証
- 全DB操作で`$wpdb->prepare()`使用
- 出力は全て`esc_html()`, `esc_attr()`, `esc_url()`でエスケープ
- JSでは`escapeHtml()`でXSS防止。DOM構築にはjQueryメソッドを使用
- ファイルアップロード: MIME検証（`finfo_file()`）、拡張子ホワイトリスト
- REST APIは`is_user_logged_in()`で保護、送信メタデータは管理者以外のREST応答から除去
- 一時ディレクトリのsession_idは`sanitize_file_name()`でサニタイズ

## 依存ライブラリ

### PHP (Composer)

- phpoffice/phpspreadsheet

### JavaScript (CDN)

- Leaflet 1.9.4 (地図表示)
- jQuery (WordPress同梱)

## 国際化

- テキストドメイン: `wp-ednasurvey`
- 翻訳ファイル: `languages/wp-ednasurvey-ja.po` / `.mo`
- ブラウザの`Accept-Language`ヘッダーを解析し、フロントエンドのみlocaleを上書き（管理画面は影響なし）
- `EdnaSurvey_I18n::get_current_language()`が`'ja'`または`'en'`を返す
- ページタイトル（`<title>`と`<h1>`）は`EdnaSurvey_Router::get_page_titles()`で一元管理
