# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Environmental DNA Citizen Survey Reporting Site Plugin (環境DNA市民調査報告サイトプラグイン) - a WordPress plugin that enables citizen scientists to submit environmental DNA survey data, and provides administrators with tools to manage users, view/export collected data, and visualize survey sites on maps.

Specifications are in `wp-ednasurvey.md` (Japanese).

## Architecture

WordPress plugin (version 1.4.0) with two main surfaces:

- **Frontend (user-facing)**: Custom URL routes under `/{USERNAME}/` providing data submission forms, offline template download, offline data upload, site listings, map visualization, site detail pages, and chat. Pages are visible only to the owning user and admins.
- **Admin dashboard**: "eDNA Survey" menu added to wp-admin with pages for data export (CSV/TSV), bulk user registration, WP_List_Table site listing, Leaflet map visualization, site detail, and settings.

### Key Design Decisions

- Multi-language: Japanese and English, auto-switched by browser `Accept-Language` header
- Users are WordPress Subscribers; access is role-based (Subscriber vs Admin)
- Map integration uses Leaflet (not Google Maps)
- Bulk user registration does not send welcome emails (handled by a separate plugin)
- `internal_sample_id` is the canonical identifier for all URLs and links (never expose numeric DB id to users)
- No backward compatibility required — designed for fresh installs only
- Routing uses `parse_request` hook with direct `REQUEST_URI` parsing (not WordPress rewrite rules). Must reset `$wp_query->is_404` and call `status_header(200)` to prevent theme 404 behavior
- Page titles (`<title>` and `<h1>`) are managed centrally via `EdnaSurvey_Router::get_page_titles()`
- HEIC→JPEG conversion preserves EXIF metadata, so GPS extraction always happens after conversion using PHP exif or exiftool CLI
- Offline submission uses a 4-step flow with server-side temp photo storage per session

### HEIC/HEIF Processing Priority

Conversion: Imagick PHP → heif-dec/heif-convert CLI → ImageMagick CLI → FFmpeg CLI
GPS extraction: PHP `exif_read_data()` → exiftool CLI (always on converted JPEG)
CLI tool paths are configurable in Settings, with auto-detection from PATH as default.
HEIC support is verified per tool: ImageMagick (`identify -list format`), heif-dec (`--list-decoders` for libde265), FFmpeg (`-decoders` for hevc).

### Naming Conventions

- DB columns (sites): `sitename_local`, `sitename_en`, `env_broad`, `env_local1`〜`env_local7`, `weather`, `wind`, `sample_id`, `survey_date`, `survey_time`, `notes`, `photo_files`
- Filtration/measurement data is NOT on the sites table. It lives in the `ednasurvey_site_filters` child table (one row per unit: `filter_type` ∈ water|air|container, `filter_index`, `filter_id`, `filter_value`). The number of instances per type is a global setting (`water_filter_count`/`air_filter_count`/`container_count`, 0–100, defaults 2/0/0). Export/import keys are `waterfilter{N}`+`watervol{N}`, `airfilter{N}`+`airvol{N}`, `container{N}`+`weight{N}`. The ID fields auto-fill `<sample_id>-N` where N is a global running number across all types in display order. Managed by `EdnaSurvey_Filter_Fields` (service) + `EdnaSurvey_Site_Filter_Model` (model); NOT in the Field Registry
- Use `correspondence` (label: 代表者 / Correspondence) — never the word "representative"
- All user-facing identifiers use `internal_sample_id` (not numeric `id`)
- Settings keys: `cmd_imagemagick`, `cmd_heif_convert`, `cmd_ffmpeg`, `cmd_exiftool`, `photo_time_threshold`
- Map settings keys: `tile_server_url`/`tile_attribution` (base map 1), `tile_server_url_2`/`tile_attribution_2` (optional base map 2 — when URL 2 is set, a "1/2" switch appears above every map; both layers stay loaded for instant switching, view fully synced), `map_default_zoom` (overview maps), `map_input_zoom` (submission location maps). All maps build base layers via `EdnaSurveyMapLayers.setup(map, mapElId)` (assets/js/frontend/map-layers.js), not raw `L.tileLayer`

## Development

This is a standard WordPress plugin. The plugin directory should be placed in `wp-content/plugins/`.

### Important: Version Bumping

When modifying JS or CSS files, bump the version in `wp-ednasurvey.php` to force cache invalidation (including Cloudflare CDN). **Two places must be updated together:**

1. Plugin header comment: `Version: x.y.z`
2. PHP constant: `EDNASURVEY_VERSION`

### WordPress Plugin Structure

- Main plugin file: `wp-ednasurvey.php` (plugin header, constants, requires, hooks)
- `includes/class-plugin.php` — Singleton orchestrator, all hook registrations
- `includes/class-router.php` — URL routing, page title management, 404 fix
- `includes/class-assets.php` — CSS/JS enqueue with i18n strings
- `includes/class-activator.php` — DB table creation (dbDelta), fresh install DROP+CREATE
- `includes/class-deactivator.php` — Cron cleanup
- `includes/class-i18n.php` — Accept-Language based locale switching
- `includes/models/` — CRUD for sites, photos, messages, custom fields (EAV)
- `includes/controllers/` — Page rendering (one per route)
- `includes/services/` — Excel (PhpSpreadsheet), photo (HEIC/EXIF), CSV, validation, notification, user import
- `includes/ajax/` — AJAX handlers (online submission, offline 4-step, chat, admin)
- `includes/cli/` — WP-CLI commands (registered only under WP-CLI). `wp ednasurvey apply-recommended-settings` applies GeneratePress / Login Customizer / Powered Cache options (typed values that `wp option patch` can't set). Used by `recommended_settings.sh` (one-shot site provisioning script in the repo root)
- `includes/admin/` — Admin pages including WP_List_Table for All Sites
- `templates/frontend/` — User-facing page templates (use `layout.php` wrapper)
- `templates/admin/` — Admin page templates
- `assets/css/` — frontend.css, admin.css, leaflet-custom.css, chat.css
- `assets/js/frontend/` — online-submission.js, offline-submission.js, map.js, sites-table.js, chat.js, map-layers.js (shared base-layer/switch helper used by every map, frontend + admin)
- `assets/js/admin/` — settings.js, all-sites.js, sites-map.js, messages.js, add-users.js, deactivate.js
- `languages/` — wp-ednasurvey-ja.po/.mo (compile with `msgfmt`)

### Offline Submission AJAX Endpoints

1. `ednasurvey_upload_temp_photos` — Step 1: upload photos to temp dir, return EXIF analysis
2. `ednasurvey_delete_temp_photo` — Step 1: delete individual temp photo
3. `ednasurvey_analyze_offline_excel` — Step 2: parse Excel, match photos, validate
4. `ednasurvey_confirm_offline` — Step 3: final submit (DB insert, move temp→permanent)

### Testing

Test on multiple browsers (Firefox, Vivaldi, Chrome) and platforms (PC, Android).
Android Firefox has specific requirements for file input elements (use native `<input type="file">` rather than programmatic `.trigger('click')`).
