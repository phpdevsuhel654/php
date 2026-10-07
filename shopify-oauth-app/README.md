# Shopify Multi-Store Learning App

A sample, production-style PHP + MySQL application demonstrating how a single Shopify
app can be installed on **multiple Shopify stores**, each with its own isolated OAuth
access token, using the **Shopify Admin REST API** (no GraphQL) via `phpclassic/php-shopify`.

This is a learning project. It intentionally has no merchant login/authentication layer
(see [Security](#security) and [Known limitations](#known-limitations)).

## Architecture

```text
                 Shopify (per store)
                        │
        install.php ──► /admin/oauth/authorize
                        │
        oauth/callback.php ◄── redirect + code
                        │
        token exchange ──► access_scopes.json (authoritative scopes)
                        │
        MySQL: shopify_stores (1 row per shop_domain, encrypted token)
                        │
        dashboard.php ──► products.php / orders.php / webhooks/*
```

Each Shopify store gets its own row in `shopify_stores`, keyed by a **unique**
`shop_domain`. Every REST call and webhook action re-derives the Shopify client
strictly from the resolved store's own token — no request ever accepts a token or
shop_domain pairing from anything other than the database, so one store's
credentials can never be applied to another store's request.

## Requirements

* PHP 8.2+ with the `sodium`, `curl`, `pdo_mysql` extensions
* MySQL 8+ (or MariaDB equivalent)
* Composer
* A Shopify Partner/Dev Dashboard app (Client ID + Client Secret)
* A public HTTPS URL for local development (e.g. `ngrok`), since Shopify requires
  HTTPS for the App URL, redirect URL, and webhook URLs

## Project structure

```text
config/config.php        Loads .env, returns app/shopify/database/security config
database/schema.sql       MySQL schema (stores, oauth_states, webhooks, delivery log)
src/Database/             PDO connection factory
src/Security/             TokenCipher (sodium), Session, Csrf
src/Shopify/              Shop domain validation, ShopifySDK client factory
src/OAuth/                State, HMAC, authorization URL, token exchange, access scopes
src/Stores/               Store persistence (install/uninstall/lookup)
src/Webhooks/             Webhook HMAC verification, webhook persistence
public/index.php          Health check
public/install.php        Starts OAuth for a given ?shop=
public/oauth/callback.php OAuth callback (HMAC, state, token exchange, scope sync)
public/dashboard.php       Per-store dashboard (status, links, webhook controls)
public/products.php       REST Admin API product listing (paginated)
public/orders.php         REST Admin API order listing (paginated)
public/webhooks/          Webhook create/delete + orders/create + app/uninstalled receivers
```

## Installation

1. Copy `.env.example` to `.env` and fill in the values (see below).
2. Install dependencies:
   ```bash
   composer install
   ```
3. Create the database and schema:
   ```bash
   mysql -u root < database/schema.sql
   ```
4. Serve the app so the URL matches your `.env` `APP_URL`, either with the PHP
   built-in server:
   ```bash
   composer serve
   ```
   or via Apache/Nginx pointed at the `public/` directory.

### Environment variables

```text
APP_ENV, APP_DEBUG, APP_URL          Application environment and public base URL
APP_ENCRYPTION_KEY                   Base64, 32-byte sodium key (see below)

SHOPIFY_CLIENT_ID                    From the Partner/Dev Dashboard app
SHOPIFY_CLIENT_SECRET                From the Partner/Dev Dashboard app (never commit)
SHOPIFY_REDIRECT_URI                 Must exactly match an Allowed redirection URL
SHOPIFY_API_VERSION                  e.g. 2026-07
SHOPIFY_SCOPES                       Comma-separated (see note below)

DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
```

Generate the encryption key:
```bash
php -r "echo base64_encode(sodium_crypto_secretbox_keygen()), PHP_EOL;"
```

> **Important:** for apps using Shopify's newer **Managed Installation** model (Dev
> Dashboard apps with "Use legacy install flow: false"), Shopify **ignores** the
> `scope` parameter sent in the authorize request and always grants exactly what is
> configured in the Dashboard's Access scopes. `SHOPIFY_SCOPES` is still sent for
> compatibility with classic apps, but don't rely on it for Managed Installation apps
> — check the Dashboard's Access scopes instead.

## Shopify Dev Dashboard configuration

| Setting | Value |
|---|---|
| App URL | Your `APP_URL`, e.g. `https://your-tunnel.example/shopify-oauth-app/public/` |
| Allowed redirection URL(s) | `{APP_URL}/oauth/callback.php` |
| Access scopes | At minimum `read_products`, `read_orders` for this app's features |
| Protected customer data access | Required separately (see [Troubleshooting](#troubleshooting)) if you need Orders/Customers data |

**Local development:** Shopify requires HTTPS for the App URL and redirect URL, so
`http://localhost` cannot be used directly. See
[Connecting to a local server](#connecting-to-a-local-server) below for the full
step-by-step tunnel setup.

**Production:** point `APP_URL` / `SHOPIFY_REDIRECT_URI` at your real HTTPS domain,
use a real TLS certificate, and store `.env` outside the web root or as
environment variables — never commit it (`.gitignore` already excludes it).

## Connecting to a local server

Shopify will not redirect OAuth or deliver webhooks to a plain `http://localhost`
URL, so your local app needs to be reachable over a public HTTPS URL while you
develop. The steps below use `ngrok` (Laragon bundles it at
`C:\laragon\bin\ngrok\ngrok.exe`; otherwise download it from ngrok.com) tunneling to
Apache/Laragon on port 80.

1. **Start your local web server** so the app is reachable locally first, e.g. via
   Laragon (Apache) at `http://localhost/shopify-oauth-app/public/`, or:
   ```bash
   composer serve
   ```
   Confirm it works before continuing — open the URL and check for
   "Application is running."

2. **Start the tunnel**, pointing at whichever port your server listens on
   (`80` for Apache/Laragon, `8000` for `composer serve`):
   ```bash
   ngrok http 80
   ```
   ngrok prints a `Forwarding` line with a public HTTPS URL, e.g.
   `https://selector-armoire-macaw.ngrok-free.dev -> http://localhost:80`.
   Keep this terminal running for the whole session — closing it ends the tunnel.

3. **Update `.env`** to use the ngrok URL (include the same subfolder path your
   local server uses, e.g. `/shopify-oauth-app/public`):
   ```bash
   APP_URL=https://YOUR-SUBDOMAIN.ngrok-free.dev/shopify-oauth-app/public
   SHOPIFY_REDIRECT_URI=https://YOUR-SUBDOMAIN.ngrok-free.dev/shopify-oauth-app/public/oauth/callback.php
   ```

4. **Update the Shopify Dev Dashboard app** with the same URLs:
   * **App URL** → `https://YOUR-SUBDOMAIN.ngrok-free.dev/shopify-oauth-app/public/`
   * **Allowed redirection URL(s)** → `https://YOUR-SUBDOMAIN.ngrok-free.dev/shopify-oauth-app/public/oauth/callback.php`

5. **Verify the tunnel reaches your app** before testing OAuth:
   ```bash
   curl -I https://YOUR-SUBDOMAIN.ngrok-free.dev/shopify-oauth-app/public/index.php
   ```
   You should get `HTTP/2 200`. If you're testing with a script/HTTP client rather
   than a real browser, add the header `ngrok-skip-browser-warning: true`, or
   ngrok's free-tier interstitial warning page will be returned instead of your
   app's real response.

6. **Install a store** to confirm the full loop works: open
   `https://YOUR-SUBDOMAIN.ngrok-free.dev/shopify-oauth-app/public/install.php`,
   enter a `*.myshopify.com` domain, and complete the Shopify consent screen. You
   should land on the store's dashboard.

> **Free-tier ngrok URLs change every time the tunnel restarts.** If you stop and
> restart `ngrok`, repeat steps 3–4 with the new URL, or OAuth/webhooks will fail
> with redirect/URL mismatches.

## Multi-store architecture

* `shopify_stores.shop_domain` is `UNIQUE` — one row per installed store.
* `ShopifyClientFactory::forShop()` always looks up the token by `shop_domain` and
  never accepts a token as caller input, so cross-store token use is structurally
  impossible.
* Reinstalling a store (`install.php` → `oauth/callback.php`) upserts the same row,
  refreshing the token and re-syncing scopes from Shopify's authoritative
  `access_scopes.json` endpoint (the OAuth token-exchange response's own `scope`
  field is not reliable for Managed Installation apps).
* Uninstalling (via the `app/uninstalled` webhook, auto-registered on install) marks
  the store `uninstalled`, blanks the token ciphertext, and removes local webhook
  records — the row itself is kept for audit/reinstall history.

## Security

* **OAuth:** single-use, expiring, DB-backed state; HMAC verified with `hash_equals`;
  shop domain validated against `^[a-z0-9][a-z0-9-]*\.myshopify\.com$`.
* **Tokens:** encrypted at rest with `sodium_crypto_secretbox`; never exposed to
  frontend JavaScript; blanked (not left as a dead live secret) on uninstall.
* **Webhooks:** HMAC verified over the raw request body (`hash_equals`, base64);
  shop identity is read only from the `X-Shopify-Shop-Domain` header, never the
  JSON body; deliveries are deduplicated via `X-Shopify-Webhook-Id` to guard
  against replay/duplicate retries.
* **CSRF:** state-changing dashboard actions (webhook create/delete) are POST-only
  forms protected by a session-bound token (`src/Security/Csrf.php`).
* **Database:** all queries use PDO prepared statements.
* **Secrets:** `.env` is git-ignored; the Client Secret is never sent to the browser.

### Known limitations

This app has **no merchant authentication/session binding** on `dashboard.php`,
`products.php`, `orders.php`, or the webhook management endpoints — anyone who
knows or guesses an installed `shop_domain` can view that store's dashboard,
products, and orders. A production app would add embedded App Bridge session-token
verification or a login system. This is out of scope for this learning project.

## Troubleshooting

* **403 "not approved to access REST endpoints with protected customer data"** —
  Orders/Customers require a separate approval in the Dev Dashboard (API access →
  Protected customer data access), independent of OAuth scopes.
* **Scope changes not taking effect** — a "silent" re-hit of `install.php` on an
  already-installed store does not refresh scopes. Fully uninstall the app from the
  store's Shopify Admin first, then reinstall to get the real "Install app" consent
  screen and a fresh token.
* **`ERR_NGROK_6024` / interstitial page** — ngrok's free tier shows an HTML warning
  to plain browser visits; add the `ngrok-skip-browser-warning: true` header when
  testing with a script/HTTP client (real Shopify server-to-server calls are
  unaffected).
* **OAuth callback redirects to a 404** — make sure redirects use `$config['app']['url']`
  (absolute), not a root-relative path, since the app is typically served from a
  subfolder (e.g. `/shopify-oauth-app/public/`).

