#!/usr/bin/env bash
set -eu
set -o pipefail 2>/dev/null || true

MODE="debug"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --prod)
      MODE="prod"
      shift
      ;;
    --debug)
      MODE="debug"
      shift
      ;;
    *)
      echo "Unknown argument: $1"
      echo "Usage: $0 [--prod|--debug]"
      exit 1
      ;;
  esac
done

cd "${PROJECT_DIR}"

if [[ ! -f ".env" ]]; then
  if [[ "${MODE}" == "prod" && -f ".env.prod.example" ]]; then
    cp .env.prod.example .env
    echo ">> .env created from .env.prod.example"
  elif [[ "${MODE}" == "debug" && -f ".env.debug.example" ]]; then
    cp .env.debug.example .env
    echo ">> .env created from .env.debug.example"
  else
    echo "Error: .env not found and fallback example file is missing."
    exit 1
  fi
fi

if [[ "${MODE}" == "prod" ]]; then
  COMPOSE_ARGS=(-f docker-compose.yml -f docker-compose.prod.yml)
else
  COMPOSE_ARGS=()
fi

FAIL=0

check_ok() {
  echo "[OK] $1"
}

check_fail() {
  echo "[FAIL] $1"
  FAIL=1
}

check_warn() {
  echo "[WARN] $1"
}

wpcli() {
  docker compose "${COMPOSE_ARGS[@]}" run --rm wpcli wp "$@"
}

check_theme() {
  local theme
  theme="$(wpcli option get stylesheet 2>/dev/null || true)"
  if [[ "${theme}" == "stage-lighting" ]]; then
    check_ok "Active theme is stage-lighting"
  else
    check_fail "Active theme is '${theme:-unknown}', expected 'stage-lighting'"
  fi
}

check_plugins() {
  local plugins=(
    "woocommerce"
    "stage-lighting-b2b"
    "stage-lighting-setup"
    "stage-lighting-importer"
    "stage-lighting-marketing"
    "nextend-facebook-connect"
  )
  local slug
  for slug in "${plugins[@]}"; do
    if wpcli plugin is-active "${slug}" >/dev/null 2>&1; then
      check_ok "Plugin active: ${slug}"
    else
      check_fail "Plugin inactive: ${slug}"
    fi
  done
}

check_pages() {
  local slugs=(
    "home"
    "products"
    "product-compare"
    "wishlist"
    "solutions"
    "projects"
    "for-business"
    "downloads-center"
    "blog"
    "order-tracking"
    "contact"
  )
  local slug id
  for slug in "${slugs[@]}"; do
    id="$(wpcli post list --post_type=page --name="${slug}" --post_status=publish --field=ID --format=ids 2>/dev/null || true)"
    if [[ -n "${id}" ]]; then
      check_ok "Page exists: ${slug} (#${id})"
    else
      check_fail "Missing published page: ${slug}"
    fi
  done
}

check_menu_locations() {
  local result
  result="$(wpcli eval 'echo (has_nav_menu("primary") && has_nav_menu("footer")) ? "ok" : "missing";' 2>/dev/null || true)"
  if [[ "${result}" == "ok" ]]; then
    check_ok "Primary and footer menu locations assigned"
  else
    check_fail "Primary/footer menu location assignment is missing"
  fi
}

check_homepage_modules_option() {
  local result
  result="$(wpcli eval '$m = get_option("stage_homepage_modules", array()); echo is_array($m) ? "ok" : "bad";' 2>/dev/null || true)"
  if [[ "${result}" == "ok" ]]; then
    check_ok "Homepage module option is readable"
  else
    check_fail "Homepage module option is invalid"
  fi
}

check_commerce_rules() {
  local taxes_enabled zones_count tax_rate_count
  taxes_enabled="$(wpcli option get woocommerce_calc_taxes 2>/dev/null || true)"
  if [[ "${taxes_enabled}" == "yes" ]]; then
    check_ok "WooCommerce tax calculation is enabled"
  else
    check_fail "WooCommerce tax calculation is disabled"
  fi

  zones_count="$(wpcli eval 'if (!class_exists("WC_Shipping_Zones")) { echo "0"; } else { echo (string) count(WC_Shipping_Zones::get_zones()); }' 2>/dev/null || true)"
  if [[ "${zones_count}" =~ ^[0-9]+$ ]] && [[ "${zones_count}" -ge 1 ]]; then
    check_ok "Shipping zones configured (${zones_count})"
  else
    check_fail "No shipping zones configured"
  fi

  tax_rate_count="$(wpcli eval 'global $wpdb; $t = $wpdb->prefix . "woocommerce_tax_rates"; echo (string) ((int) $wpdb->get_var("SELECT COUNT(*) FROM {$t}"));' 2>/dev/null || true)"
  if [[ "${tax_rate_count}" =~ ^[0-9]+$ ]] && [[ "${tax_rate_count}" -ge 1 ]]; then
    check_ok "Tax rates configured (${tax_rate_count})"
  else
    check_fail "No tax rates configured"
  fi
}

check_social_login_config() {
  local plugin_active social_option_count
  if wpcli plugin is-active nextend-facebook-connect >/dev/null 2>&1; then
    plugin_active="yes"
  else
    plugin_active="no"
  fi

  if [[ "${plugin_active}" != "yes" ]]; then
    check_fail "Social login plugin is not active"
    return
  fi

  social_option_count="$(wpcli eval '
global $wpdb;
$table = $wpdb->prefix . "options";
$rows = (array) $wpdb->get_results("SELECT option_name, option_value FROM {$table} WHERE option_name LIKE \"nsl_%\" OR option_name LIKE \"nextend_%\" LIMIT 300", ARRAY_A);
$matched = 0;
foreach ($rows as $row) {
    $value = strtolower((string) ($row["option_value"] ?? ""));
    if (false !== strpos($value, "client_id") || false !== strpos($value, "app_id") || false !== strpos($value, "facebook") || false !== strpos($value, "google")) {
        if (strlen(trim($value)) > 20) {
            $matched++;
        }
    }
}
echo (string) $matched;
' 2>/dev/null || true)"

  if [[ "${social_option_count}" =~ ^[0-9]+$ ]] && [[ "${social_option_count}" -ge 1 ]]; then
    check_ok "Social login appears configured (${social_option_count} provider option rows)"
  else
    check_fail "Social login plugin active but provider credentials may be missing"
  fi
}

check_social_login_redirect_domain() {
  local redirect_status
  redirect_status="$(wpcli eval '
$home_host = wp_parse_url(home_url("/"), PHP_URL_HOST);
$home_host = is_string($home_host) ? strtolower($home_host) : "";
if ("" === $home_host) {
    echo "unknown";
} else {
    global $wpdb;
    $table = $wpdb->prefix . "options";
    $rows = (array) $wpdb->get_results("SELECT option_value FROM {$table} WHERE option_name LIKE \"nsl_%\" OR option_name LIKE \"nextend_%\" LIMIT 500");
    $hosts = array();
    foreach ($rows as $row) {
        $value = (string) ($row->option_value ?? "");
        if ("" === $value) {
            continue;
        }
        if (preg_match_all("#https?://[^\\s\"<>]+#i", $value, $matches)) {
            foreach ((array) ($matches[0] ?? array()) as $url) {
                $url_l = strtolower((string) $url);
                if (false === strpos($url_l, "callback") && false === strpos($url_l, "redirect")) {
                    continue;
                }
                $host = wp_parse_url($url, PHP_URL_HOST);
                if (is_string($host) && "" !== $host) {
                    $hosts[] = strtolower($host);
                }
            }
        }
    }
    $hosts = array_values(array_unique($hosts));
    if (empty($hosts)) {
        echo "unknown";
    } elseif (in_array($home_host, $hosts, true)) {
        echo "ok";
    } else {
        echo "mismatch:" . implode(",", $hosts);
    }
}
' 2>/dev/null || true)"

  if [[ "${redirect_status}" == "ok" ]]; then
    check_ok "Social login callback domain matches site domain"
  elif [[ "${redirect_status}" == mismatch:* ]]; then
    check_warn "Social login callback domain may mismatch site domain (${redirect_status#mismatch:})"
  else
    check_warn "Social login callback URLs not detected; verify provider callback domain manually"
  fi
}

check_live_chat_config() {
  local live_chat_status
  live_chat_status="$(wpcli eval '
$s = get_option("stage_marketing_settings", array());
$provider = is_array($s) && isset($s["live_chat_provider"]) ? (string) $s["live_chat_provider"] : "none";
$ok = false;
if ("tawk" === $provider) {
    $ok = !empty($s["tawk_property_id"]) && !empty($s["tawk_widget_id"]);
} elseif ("tidio" === $provider) {
    $ok = !empty($s["tidio_public_key"]);
} elseif ("custom" === $provider) {
    $ok = !empty(trim((string) ($s["live_chat_custom"] ?? "")));
}
if ("none" === $provider) {
    echo "disabled";
} else {
    echo $ok ? "configured" : "missing";
}
' 2>/dev/null || true)"

  if [[ "${live_chat_status}" == "configured" ]]; then
    check_ok "Live chat is configured"
  elif [[ "${live_chat_status}" == "disabled" ]]; then
    check_fail "Live chat is disabled"
  else
    check_fail "Live chat provider selected but key/script is missing"
  fi
}

echo "=== Stage Lighting Site Audit ==="
echo "Mode: ${MODE}"

check_theme
check_plugins
check_pages
check_menu_locations
check_homepage_modules_option
check_commerce_rules
check_social_login_config
check_social_login_redirect_domain
check_live_chat_config

if [[ "${FAIL}" -eq 0 ]]; then
  echo "Audit passed."
  exit 0
fi

echo "Audit completed with failures."
exit 1
