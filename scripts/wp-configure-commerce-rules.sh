#!/usr/bin/env bash
set -eu
set -o pipefail 2>/dev/null || true

# Configure WooCommerce shipping/tax rules from JSON template.
# Usage:
#   bash scripts/wp-configure-commerce-rules.sh
#   bash scripts/wp-configure-commerce-rules.sh --prod
#   bash scripts/wp-configure-commerce-rules.sh --rules config/commerce-rules.prod.json

MODE="debug"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RULES_PATH=""

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
    --rules)
      RULES_PATH="${2:-}"
      if [[ -z "${RULES_PATH}" ]]; then
        echo "Error: --rules requires a file path"
        exit 1
      fi
      shift 2
      ;;
    *)
      echo "Unknown argument: $1"
      echo "Usage: $0 [--prod|--debug] [--rules <json-file>]"
      exit 1
      ;;
  esac
done

cd "${PROJECT_DIR}"

if [[ -z "${RULES_PATH}" ]]; then
  if [[ "${MODE}" == "prod" ]]; then
    RULES_PATH="config/commerce-rules.prod.json"
  else
    RULES_PATH="config/commerce-rules.debug.json"
  fi
fi

if [[ ! -f "${RULES_PATH}" ]]; then
  echo "Rules file not found: ${RULES_PATH}"
  exit 1
fi

if [[ "${MODE}" == "prod" ]]; then
  COMPOSE_ARGS=(-f docker-compose.yml -f docker-compose.prod.yml)
else
  COMPOSE_ARGS=()
fi

echo ">> Ensuring containers are up..."
docker compose "${COMPOSE_ARGS[@]}" up -d

WP_CMD=(docker compose "${COMPOSE_ARGS[@]}" exec -T wpcli wp --path=/var/www/html --allow-root)

if ! "${WP_CMD[@]}" core is-installed >/dev/null 2>&1; then
  echo "WordPress is not installed yet. Finish installer in browser first."
  exit 1
fi

if ! "${WP_CMD[@]}" plugin is-active woocommerce >/dev/null 2>&1; then
  echo "WooCommerce is not active. Run scripts/wp-apply-project-setup.sh first."
  exit 1
fi

echo ">> Configuring tax calculation options..."
"${WP_CMD[@]}" option update woocommerce_calc_taxes yes
"${WP_CMD[@]}" option update woocommerce_tax_based_on shipping
"${WP_CMD[@]}" option update woocommerce_prices_include_tax no
"${WP_CMD[@]}" option update woocommerce_shipping_cost_requires_address yes

if command -v base64 >/dev/null 2>&1; then
  RULES_JSON_B64="$(base64 -w 0 "${RULES_PATH}" 2>/dev/null || base64 "${RULES_PATH}" | tr -d '\n')"
else
  echo "base64 command not found."
  exit 1
fi

echo ">> Applying shipping zones and tax rates from ${RULES_PATH}..."
STAGE_COMMERCE_RULES_B64="${RULES_JSON_B64}" "${WP_CMD[@]}" eval '
$rules_b64 = getenv("STAGE_COMMERCE_RULES_B64");
if (empty($rules_b64)) {
    echo "Missing rules payload\n";
    return;
}

$json_raw = base64_decode($rules_b64, true);
if (false === $json_raw) {
    echo "Failed to decode rules payload\n";
    return;
}

$rules = json_decode($json_raw, true);
if (!is_array($rules)) {
    echo "Invalid JSON rules file\n";
    return;
}

if (!class_exists("WC_Shipping_Zones")) {
    echo "Shipping API unavailable\n";
    return;
}

function stage_wc_find_zone_by_name($name) {
    $zones = WC_Shipping_Zones::get_zones();
    foreach ($zones as $zone) {
        if (!empty($zone["zone_name"]) && $zone["zone_name"] === $name) {
            return (int) $zone["zone_id"];
        }
    }
    return 0;
}

function stage_wc_upsert_shipping_method($zone_obj, $method_id, $settings) {
    $methods = $zone_obj->get_shipping_methods(true);
    foreach ($methods as $method) {
        if ($method->id !== $method_id) {
            continue;
        }
        $current_title = isset($method->instance_settings["title"]) ? (string) $method->instance_settings["title"] : "";
        if (!empty($settings["title"]) && $current_title === $settings["title"]) {
            $option_key = "woocommerce_" . $method_id . "_" . $method->instance_id . "_settings";
            $merged = array_merge((array) $method->instance_settings, $settings);
            update_option($option_key, $merged);
            return;
        }
    }
    $instance_id = $zone_obj->add_shipping_method($method_id);
    $option_key = "woocommerce_" . $method_id . "_" . $instance_id . "_settings";
    update_option($option_key, $settings);
}

$zone_configs = isset($rules["shipping_zones"]) && is_array($rules["shipping_zones"]) ? $rules["shipping_zones"] : array();
foreach ($zone_configs as $cfg) {
    $name = isset($cfg["name"]) ? trim((string) $cfg["name"]) : "";
    if ("" === $name) {
        continue;
    }
    $zone_id = stage_wc_find_zone_by_name($name);
    if ($zone_id > 0) {
        $zone = new WC_Shipping_Zone($zone_id);
    } else {
        $zone = new WC_Shipping_Zone();
        $zone->set_zone_name($name);
        $zone->save();
    }

    $locations = array();
    $countries = isset($cfg["countries"]) && is_array($cfg["countries"]) ? $cfg["countries"] : array();
    foreach ($countries as $country_code) {
        $country_code = strtoupper(trim((string) $country_code));
        if ("" !== $country_code) {
            $locations[] = array("code" => $country_code, "type" => "country");
        }
    }
    if (!empty($locations)) {
        $zone->set_zone_locations($locations);
        $zone->save();
    }

    $methods = isset($cfg["methods"]) && is_array($cfg["methods"]) ? $cfg["methods"] : array();
    foreach ($methods as $method_cfg) {
        $method_id = isset($method_cfg["id"]) ? (string) $method_cfg["id"] : "";
        $settings = isset($method_cfg["settings"]) && is_array($method_cfg["settings"]) ? $method_cfg["settings"] : array();
        if ("" !== $method_id) {
            stage_wc_upsert_shipping_method($zone, $method_id, $settings);
        }
    }
    echo "Zone synced: " . $name . "\n";
}

global $wpdb;
$rates_table = $wpdb->prefix . "woocommerce_tax_rates";
$loc_table = $wpdb->prefix . "woocommerce_tax_rate_locations";

function stage_wc_upsert_tax_rate($country, $rate, $name) {
    global $wpdb, $rates_table, $loc_table;

    $existing_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT tax_rate_id FROM {$rates_table} WHERE tax_rate_country=%s AND tax_rate_name=%s AND tax_rate_class=%s LIMIT 1",
        $country,
        $name,
        ""
    ));

    if ($existing_id > 0) {
        $wpdb->update(
            $rates_table,
            array(
                "tax_rate"          => $rate,
                "tax_rate_shipping" => 1,
                "tax_rate_priority" => 1,
                "tax_rate_compound" => 0,
            ),
            array("tax_rate_id" => $existing_id),
            array("%s", "%d", "%d", "%d"),
            array("%d")
        );
        return $existing_id;
    }

    $wpdb->insert(
        $rates_table,
        array(
            "tax_rate_country"  => $country,
            "tax_rate_state"    => "",
            "tax_rate"          => $rate,
            "tax_rate_name"     => $name,
            "tax_rate_priority" => 1,
            "tax_rate_compound" => 0,
            "tax_rate_shipping" => 1,
            "tax_rate_order"    => 0,
            "tax_rate_class"    => "",
        ),
        array("%s","%s","%s","%s","%d","%d","%d","%d","%s")
    );
    $tax_rate_id = (int) $wpdb->insert_id;
    if ($tax_rate_id > 0) {
        $wpdb->insert(
            $loc_table,
            array(
                "location_code" => $country,
                "tax_rate_id"   => $tax_rate_id,
                "location_type" => "country",
            ),
            array("%s","%d","%s")
        );
    }
    return $tax_rate_id;
}

$tax_configs = isset($rules["tax_rates"]) && is_array($rules["tax_rates"]) ? $rules["tax_rates"] : array();
foreach ($tax_configs as $tax_cfg) {
    $tax_name = isset($tax_cfg["name"]) ? trim((string) $tax_cfg["name"]) : "";
    $tax_rate = isset($tax_cfg["rate"]) ? trim((string) $tax_cfg["rate"]) : "";
    $countries = isset($tax_cfg["countries"]) && is_array($tax_cfg["countries"]) ? $tax_cfg["countries"] : array();
    if ("" === $tax_name || "" === $tax_rate || empty($countries)) {
        continue;
    }
    foreach ($countries as $country) {
        $country = strtoupper(trim((string) $country));
        if ("" !== $country) {
            stage_wc_upsert_tax_rate($country, $tax_rate, $tax_name);
        }
    }
}
echo "Tax rates synced\n";
'

echo "Commerce shipping/tax baseline is configured."
