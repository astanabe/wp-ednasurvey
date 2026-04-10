# wp-ednasurvey

Environmental DNA Citizen Survey Reporting Site Plugin for WordPress.

環境DNA市民調査報告サイトプラグイン

## Features

- Online and offline survey data submission (Excel template)
- Photo upload with HEIC/HEIF support and EXIF GPS extraction
- Leaflet map visualization for survey sites
- Admin dashboard: data export (CSV/TSV), bulk user registration, site management (WP_List_Table), chat
- Bilingual: Japanese / English (auto-detected by browser language)

## Requirements

- WordPress 6.4+
- Theme: [GeneratePress](https://generatepress.com/)
- PHP 8.1+
- PHP extensions: zip (required), exif / mbstring (recommended)
- CLI tools (recommended): heif-dec or ffmpeg (HEIC conversion), exiftool (GPS fallback)

## Installation

1. Download the [latest ZIP](https://github.com/astanabe/wp-ednasurvey/archive/refs/heads/main.zip)
2. WordPress Admin → Plugins → Add New → Upload Plugin → select the ZIP
3. Activate

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
