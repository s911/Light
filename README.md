# Stage Lighting WordPress Starter

This repository contains an implementation starter for the stage lighting independent website:

- WordPress runtime via Docker Compose
- Custom dark-tech theme (`stage-lighting`)
- B2B bulk quote plugin (`stage-lighting-b2b`)
- One-click setup plugin (`stage-lighting-setup`)
- Dedicated page templates for Products / Solutions / Projects / Downloads / Blog
- Custom single product page template with specs/downloads/recommendations
- Product compare feature (up to 4 products)
- Built-in B2B lead inbox and tracking events
- Excel/CSV product importer with dynamic attributes
- Marketing plugin: social links, GA/Pixel, newsletter popup

## 1) Quick Start

1. Copy env file:

```bash
cp .env.example .env
```

2. Start services:

```bash
docker compose up -d
```

3. Open WordPress installer:

- `http://localhost:8080` (or your `WORDPRESS_PORT`)

## 1.1) Debian Debug vs Production Compose

Debug mode (recommended for code debugging on Debian):

1. Copy debug env:

```bash
cp .env.debug.example .env
```

2. Start (auto-loads `docker-compose.override.yml`):

```bash
docker compose up -d
```

This mode includes:

- WordPress code bind-mount for local theme/plugin debugging
- MySQL host port mapping (`MYSQL_PORT`, default `3306`)

Production mode (safer defaults):

1. Copy prod env:

```bash
cp .env.prod.example .env
```

2. Start with explicit files (does not auto-load debug override):

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

This mode includes:

- No MySQL host port exposure
- Only named volumes for WordPress and MySQL data
- Project theme/plugins mounted into WordPress for custom site behavior

## 1.2) Debian One-click Deploy Script

Script path:

- `scripts/debian-one-click-deploy.sh`

Make executable:

```bash
chmod +x scripts/debian-one-click-deploy.sh
```

If you copied files from Windows and see errors like `set: pipefail: invalid option name`, normalize line endings first:

```bash
sed -i 's/\r$//' scripts/debian-one-click-deploy.sh
```

Debug deployment:

```bash
bash scripts/debian-one-click-deploy.sh --mode debug
```

Production deployment:

```bash
bash scripts/debian-one-click-deploy.sh --mode prod
```

With proxy (your network requirement):

```bash
bash scripts/debian-one-click-deploy.sh --mode debug --proxy http://10.144.1.10:8080
```

What the script does:

- Installs Docker + Docker Compose plugin if missing
- Enables and starts Docker service
- Prepares `.env` from debug/prod template when `.env` is absent
- Starts stack in debug or prod mode
- Runs basic health check for WordPress HTTP availability

## 1.3) Apply Project Theme/Plugins After Deploy

If frontend still shows default WordPress theme, run:

```bash
chmod +x scripts/wp-apply-project-setup.sh
bash scripts/wp-apply-project-setup.sh
```

For production compose mode:

```bash
bash scripts/wp-apply-project-setup.sh --prod
```

This script will:

- Ensure containers are running
- Ensure WooCommerce is installed/activated
- Ensure Nextend Social Login is installed/activated
- Activate `stage-lighting` theme
- Activate `stage-lighting-b2b`, `stage-lighting-setup`, and `stage-lighting-importer` plugins
- Activate `stage-lighting-marketing` plugin
- Execute one-click initializer (pages, menus, taxonomy, sample data)
- Flush rewrite rules

Optional commerce baseline (shipping/tax):

```bash
# Debug mode
bash scripts/wp-configure-commerce-rules.sh

# Production mode
bash scripts/wp-configure-commerce-rules.sh --prod

# Custom rules template
bash scripts/wp-configure-commerce-rules.sh --rules config/commerce-rules.prod.json
```

This script will:

- Enable WooCommerce tax calculation
- Create/update shipping zones: United States / Europe / Asia Pacific
- Set baseline flat-rate/free-shipping rules
- Seed destination tax rates (EU VAT / UK VAT / AU GST / SG GST)
- Auto-load rules by mode:
  - `config/commerce-rules.debug.json` for debug
  - `config/commerce-rules.prod.json` for prod
- Or load a custom JSON via `--rules`

## 1.4) One-command Reload After Code Changes

Script path:

- `scripts/reload.sh`

Make executable:

```bash
chmod +x scripts/reload.sh
```

Common usage:

```bash
# Debug mode quick reload
bash scripts/reload.sh

# Production mode quick reload
bash scripts/reload.sh --prod

# Rebuild containers when compose/env/image changed
bash scripts/reload.sh --prod --rebuild

# Include short logs for troubleshooting
bash scripts/reload.sh --logs
```

What it does:

- Auto-creates `.env` from debug/prod template when missing
- Restart (or rebuild) stack
- Re-apply WordPress theme/plugins/setup via `wp-apply-project-setup.sh`
- Show compose status (and optional recent logs)

## 1.5) Go-live Audit Script

Script path:

- `scripts/site-audit.sh`

Usage:

```bash
# Debug environment audit
bash scripts/site-audit.sh

# Production environment audit
bash scripts/site-audit.sh --prod
```

Checks included:

- Theme status (`stage-lighting`)
- Required plugin activation
- Required published pages (home/products/solutions/projects/for-business/downloads/blog/contact)
- Primary/footer menu location assignment
- Homepage module switch option readability
- WooCommerce tax enabled + shipping zones + tax rate rows
- Social login plugin activation + basic provider configuration signal
- Social login callback domain consistency hint (warn-only)
- Live chat provider configuration status

## 2) Theme and Plugin Activation

After first WordPress login:

1. Go to `Appearance -> Themes`, activate `Stage Lighting Theme`
2. Go to `Plugins`, activate `Stage Lighting B2B Quote`
3. Activate `Stage Lighting Site Setup`
4. Go to `Tools -> Stage Lighting Setup`, click `Run Initialization`
5. In the same screen, use `Homepage Module Switches` to toggle front-page sections without editing code
6. Initialization will auto-create pages, menus, WooCommerce categories/tags, product attributes, and bulk demo data:
   - 12 demo products (with category/tag/power/price/sales/rating/download links)
   - 6 demo projects
   - 6 demo downloads
   - 8 demo blog posts
7. To remove test data, go to `Tools -> Stage Lighting Setup` and click `Clear Demo Content` (only deletes demo-marked records)

## Product Manual Management Enhancements

In admin `Products` list, additional operation helpers are enabled:

- Power filter dropdown (`All Power`) for quick list filtering
- Extra columns: `Power`, `Price (USD)`, `Video`
- Row action `Duplicate` to clone a product into draft with taxonomy/meta copied

## 3) Menu Structure

Create menu in `Appearance -> Menus`:

- Home
- Products
- Solutions
- Projects
- For Business
- About Us
- Blog / News
- Contact

Assign to `Primary Menu` and `Footer Menu`.

## 4) WooCommerce Setup

Install and configure:

- WooCommerce
- WooCommerce PayPal Payments
- WooCommerce Stripe Gateway

Then set:

- Shipping zones and rates
- Tax rules (VAT/GST by destination)
- Product categories according to PRD

## 5) Proxy Requirement for Dependency Installation

If you run any command line installation, use:

`--proxy=http://10.144.1.10:8080`

Examples:

```bash
pip install package-name --proxy=http://10.144.1.10:8080
npm install package-name --proxy=http://10.144.1.10:8080
```

## 6) Delivery Documents

- `WordPress服务器部署与操作手册.md`
- `WordPress后台操作手册-运营版.md`
- `Ubuntu服务器部署命令手册-代理版.md`
- `上线验收清单-舞台灯独立站.md`

## 7) Product Import Template

- CSV template path: `templates/stage-lighting-products-import-template.csv`
- 50-SKU starter CSV: `templates/stage-lighting-products-import-50sku.csv`
- Dynamic importer template: `templates/stage-lighting-dynamic-import-template.csv`
- Import from `WooCommerce -> Products -> Import`
- Includes recommended columns for:
  - Categories and solution tags
  - Global attributes (Power/Application/Control Protocol/Certification)
  - Custom download links meta (`Meta:stage_download_links`)

## 7.1) Excel/CSV Dynamic Importer (Recommended)

Importer path in WP admin:

- `Tools -> Stage Product Importer`

Supported file types:

- `.xlsx` (first sheet)
- `.csv`

Column rules:

- `name` (required)
- `sku` (recommended for update matching)
- `description`
- `short_description`
- `regular_price`
- `sale_price`
- `stock`
- `categories` (comma or `|` separated names)
- `tags` (comma or `|` separated names)
- `download_links` (`|` separated URLs)
- `download_items` (`Type::URL|Type::URL`, for grouped downloads in product detail)
- `video_url` (YouTube/Vimeo/embed URL for product detail page)
- Any dynamic parameter: `attr:Parameter Name`
  - Examples: `attr:Power`, `attr:Beam Angle`, `attr:Control Protocol`
  - Multiple values use comma or `|`

Dynamic attributes behavior:

- No hardcoded limit on parameter columns
- Any `attr:*` column automatically becomes a visible WooCommerce product attribute
- New parameter columns can be added directly in Excel without code changes

Import workflow (with safety check):

1. Upload file and click `Preview Import`
2. Review planned counts (import/update/skip) and detected dynamic attributes
3. Click `Confirm Import` to apply changes
4. If errors occur, click `Download Error CSV` for failed row details
5. Import jobs are recorded in `Tools -> Stage Import Logs` (time/operator/file/stats/error report)

## 7.2) Product Compare (P1)

- Compare page URL: `/product-compare`
- Add compare from product listing/home/detail via `Add Compare` button
- Up to 4 products can be compared side-by-side
- Compare item count appears in header: `Compare (N)`

## 7.3) Solutions / Blog / Downloads Enhancements

- `Solutions` page supports scene landing mode via query string: `?scene=concert-touring`
  - Shows scene intro, recommended products, and related projects
- `Blog` page supports category and tag filtering via query string
  - `?cat=<category-slug>`
  - `?tag=<tag-slug>`
  - Includes SEO internal links block
- `Downloads` page supports type filtering:
  - `All / Manual / Certificate / IES`

## 8) Product Downloads UI Field

- After activating `stage-lighting-setup`, each product edit page includes `Product Download Links` meta box.
- Add one file URL per line; these links will appear in the single product `Downloads` section.

## 9) B2B Leads and Marketing Tracking

- B2B submissions are saved in WordPress admin under `B2B Leads`.
- Lead status workflow supported: `new`, `contacted`, `quoted`, `won`, `lost`.
- Filter leads by status in the B2B Leads list.
- Track and manage `Follow-up Date` per lead in lead detail page.
- Lead list supports date-range filtering (`follow_up_from` / `follow_up_to`).
- Export CSV from the B2B Leads top toolbar, preserving active filters.
- Overdue leads are marked in the `Overdue` column.
- Daily overdue lead digest email is auto-sent to Sales Email by WP-Cron.
- Manual trigger is available in `Settings -> Stage B2B Settings` via `Send Reminder Now`.
- Quote form supports product quick-select (multi-select + search) and manual product input.
- Quote form upload field supports drag-and-drop UX (falls back to normal file select).
- Quote CTA clicks trigger `b2b_quote_click` event.
- Successful quote submissions trigger `b2b_quote_submit_success` event.
- Floating WhatsApp button click triggers `whatsapp_click` event.
- Events are sent to `gtag`, `dataLayer`, and `fbq` when those trackers are present.

## 10) B2B Contact Settings

- Go to `Settings -> Stage B2B Settings`
- Configure:
  - Sales email receiver
  - WhatsApp number (digits only, with country code)
  - WhatsApp display text
- Footer contact info and floating WhatsApp button use these settings automatically.

## 11) Marketing Settings

- Go to `Settings -> Stage Marketing Settings`
- Configure:
  - Instagram/Facebook/TikTok/YouTube links
  - GA4 Measurement ID
  - Meta Pixel ID
  - Newsletter popup enable/disable
  - Live chat provider (Tawk.to / Tidio / Custom Script)
- Newsletter subscribers are stored in admin menu: `Newsletter Subscribers`

## 11.2) Social Login (Google/Facebook)

- Social login plugin: `Nextend Social Login` (`nextend-facebook-connect`)
- Path: `Settings -> Nextend Social Login`
- Configure OAuth credentials from Google/Facebook developer console:
  - Authorized redirect URL should use your live domain callback generated by Nextend
- Enable buttons on WooCommerce login/register page after provider setup

## 11.1) Wishlist + Order Tracking

- Wishlist page URL: `/wishlist`
- Add/Remove wishlist from home/products/product detail cards
- Header shows current wishlist count: `Wishlist (N)`
- Logged-in users: wishlist is persisted into account (`user_meta`) and synced across sessions
- My Account includes a `Wishlist` endpoint entry
- Order tracking page URL: `/order-tracking`
- Tracking form uses WooCommerce shortcode: `[woocommerce_order_tracking]`

## 12) Structured Data (SEO)

- Theme outputs JSON-LD for:
  - `Organization` and `WebSite` (site-wide)
  - `Product` with offer data on single product pages
