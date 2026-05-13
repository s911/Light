# Stage Lighting WordPress Starter

This repository contains an implementation starter for the stage lighting independent website:

- WordPress runtime via Docker Compose
- Custom dark-tech theme (`stage-lighting`)
- B2B bulk quote plugin (`stage-lighting-b2b`)
- One-click setup plugin (`stage-lighting-setup`)
- Dedicated page templates for Products / Solutions / Projects / Downloads / Blog
- Custom single product page template with specs/downloads/recommendations
- Built-in B2B lead inbox and tracking events

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
- Activate `stage-lighting` theme
- Activate `stage-lighting-b2b` and `stage-lighting-setup` plugins
- Execute one-click initializer (pages, menus, taxonomy, sample data)
- Flush rewrite rules

## 2) Theme and Plugin Activation

After first WordPress login:

1. Go to `Appearance -> Themes`, activate `Stage Lighting Theme`
2. Go to `Plugins`, activate `Stage Lighting B2B Quote`
3. Activate `Stage Lighting Site Setup`
4. Go to `Tools -> Stage Lighting Setup`, click `Run Initialization`
5. This will auto-create pages, menus, WooCommerce categories/tags, product attributes, and a sample product

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
- Import from `WooCommerce -> Products -> Import`
- Includes recommended columns for:
  - Categories and solution tags
  - Global attributes (Power/Application/Control Protocol/Certification)
  - Custom download links meta (`Meta:stage_download_links`)

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
