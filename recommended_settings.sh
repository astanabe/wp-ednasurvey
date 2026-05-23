#!/usr/bin/env bash
#
# recommended_settings.sh
#
# Provision a fresh WordPress install for the eDNA Survey reporting site:
# updates core, installs GeneratePress + the recommended plugins, clears
# widgets/menus, and applies the recommended theme/plugin settings.
#
# Requirements: WP-CLI (https://wp-cli.org/) and an already-installed WordPress.
#
# Usage:
#   ./recommended_settings.sh
#
#   # Point at a WordPress install other than the current directory:
#   WP_PATH=/var/www/html ./recommended_settings.sh
#
#   # Use a specific wp binary:
#   WP_BIN=/usr/local/bin/wp ./recommended_settings.sh
#
# Notes:
# - Theme/plugin settings are applied once here; they remain editable in the
#   admin UI afterwards.
# - Settings that contain typed booleans (GeneratePress, Login Customizer,
#   Powered Cache) are applied by the wp-ednasurvey plugin's WP-CLI command
#   (`wp ednasurvey apply-recommended-settings`), because `wp option patch`
#   would store them as strings.

set -euo pipefail

# ---------------------------------------------------------------------------
# WP-CLI wrapper
# ---------------------------------------------------------------------------
WP_PATH="${WP_PATH:-$(pwd)}"
WP=("${WP_BIN:-wp}" "--path=${WP_PATH}")
if [ "$(id -u)" -eq 0 ]; then
    WP+=(--allow-root)
fi

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }
warn() { printf '\033[1;33mWARN:\033[0m %s\n' "$1" >&2; }

# ---------------------------------------------------------------------------
# Pre-flight checks
# ---------------------------------------------------------------------------
if ! command -v "${WP_BIN:-wp}" >/dev/null 2>&1; then
    echo "ERROR: WP-CLI ('${WP_BIN:-wp}') not found in PATH." >&2
    exit 1
fi
if ! "${WP[@]}" core is-installed >/dev/null 2>&1; then
    echo "ERROR: No WordPress install found at '${WP_PATH}'." >&2
    echo "       Run from the WordPress root or set WP_PATH=/path/to/wordpress." >&2
    exit 1
fi

# ---------------------------------------------------------------------------
# 1. Update WordPress core + enable automatic updates
# ---------------------------------------------------------------------------
step "1. Updating WordPress core and enabling auto-updates"
"${WP[@]}" core update
"${WP[@]}" core update-db
# 'enabled' = auto-update for all (major) new versions; minor are on by default.
"${WP[@]}" option update auto_update_core_major enabled

# ---------------------------------------------------------------------------
# 2. Install GeneratePress and enable auto-updates
# ---------------------------------------------------------------------------
step "2. Installing GeneratePress theme"
"${WP[@]}" theme install generatepress --activate
"${WP[@]}" theme auto-updates enable generatepress || warn "Could not enable theme auto-updates."

# ---------------------------------------------------------------------------
# 3. Remove all widgets from every widget area
#    (Right/Left Sidebar, Header, Footer Widget 1-5, Footer Bar, Top Bar, ...)
#    Done by emptying sidebars_widgets so it works for both classic and block
#    widget setups; run after the theme is active.
# ---------------------------------------------------------------------------
step "3. Removing all widgets from all widget areas"
"${WP[@]}" option update sidebars_widgets '{"array_version":3,"wp_inactive_widgets":[]}' --format=json

# ---------------------------------------------------------------------------
# 4. Delete all menus (if any)
# ---------------------------------------------------------------------------
step "4. Deleting all navigation menus"
MENU_IDS="$("${WP[@]}" menu list --format=ids 2>/dev/null || true)"
if [ -n "${MENU_IDS}" ]; then
    for menu_id in ${MENU_IDS}; do
        "${WP[@]}" menu delete "${menu_id}" || warn "Could not delete menu ${menu_id}."
    done
else
    echo "  (no menus to delete)"
fi

# ---------------------------------------------------------------------------
# 5. Install + activate plugins in order, enabling auto-updates where possible
# ---------------------------------------------------------------------------
step "5. Installing and activating plugins"
WPORG_PLUGINS=(
    wp-multibyte-patch
    wps-hide-login
    wps-limit-login
    stop-user-enumeration
    simple-cloudflare-turnstile
    protect-uploads
    disable-xml-rpc
    disable-google-fonts
    login-customizer
    imagemagick-engine
    powered-cache
)
for slug in "${WPORG_PLUGINS[@]}"; do
    echo "  - ${slug}"
    if "${WP[@]}" plugin install "${slug}" --activate; then
        "${WP[@]}" plugin auto-updates enable "${slug}" || warn "Auto-updates not enabled for ${slug}."
    else
        warn "Failed to install/activate ${slug}; continuing."
    fi
done

# eDNA Survey plugin (from GitHub; folder becomes wp-ednasurvey-main)
echo "  - wp-ednasurvey (GitHub)"
"${WP[@]}" plugin install "https://github.com/astanabe/wp-ednasurvey/archive/refs/heads/main.zip" --activate --force
EDNA_DIR="$("${WP[@]}" plugin list --field=name 2>/dev/null | grep -E '^wp-ednasurvey' | head -1 || true)"
if [ -n "${EDNA_DIR}" ]; then
    "${WP[@]}" plugin auto-updates enable "${EDNA_DIR}" || warn "Auto-updates not enabled for ${EDNA_DIR} (no update source; expected)."
fi

# ---------------------------------------------------------------------------
# 6 + 7. Apply recommended GeneratePress, Login Customizer and Powered Cache
#         settings (provided by the wp-ednasurvey plugin's WP-CLI command).
# ---------------------------------------------------------------------------
step "6+7. Applying recommended theme / Login Customizer / Powered Cache settings"
"${WP[@]}" ednasurvey apply-recommended-settings

# ---------------------------------------------------------------------------
# Done. Flush caches so changes take effect immediately.
# ---------------------------------------------------------------------------
"${WP[@]}" cache flush >/dev/null 2>&1 || true

step "Setup complete."
echo "Site: $("${WP[@]}" option get siteurl 2>/dev/null || echo "${WP_PATH}")"
