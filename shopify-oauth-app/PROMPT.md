# Shopify Dev Dashboard Multi-Store App — Complete Development Prompt

I want to create a **sample production-style application** to learn and demonstrate how to build a **Shopify Dev Dashboard App** that supports **multiple Shopify stores** using PHP and MySQL.

The application should implement Shopify OAuth installation, securely store store-specific access tokens, make Shopify Admin API calls, and provide store-specific features such as product/order synchronization and webhook management.

Please act as a **Senior PHP + Shopify App Developer** and guide me through the implementation **step by step**.

---

## 1. Technology Stack

Use the following technology stack:

### Backend

* PHP 8.2+
* MySQL 8+
* Composer
* PHP sessions where appropriate
* PDO for MySQL
* Shopify Admin REST API
* Shopify Admin API

### Shopify Library

For Shopify Admin API integration, use:

* `phpclassic/php-shopify`

GitHub repository:

https://github.com/phpclassic/php-shopify

Do not replace this library with another Shopify PHP SDK unless there is a specific technical limitation that makes it necessary.

### Frontend

Keep the frontend simple initially.

Use:

* HTML
* CSS
* Vanilla JavaScript
* Bootstrap if required

The primary objective is to understand the **Shopify App + OAuth + Multi-Store architecture**, not frontend design.

---

# 2. Required Architecture

The application must follow this overall architecture:

```text
                 ┌─────────────────────┐
                 │     Your PHP App     │
                 └──────────┬──────────┘
                            │
                            ▼
                 ┌─────────────────────┐
                 │   OAuth Install     │
                 └──────────┬──────────┘
                            │
                            ▼
                 ┌─────────────────────┐
                 │ Merchant Approves   │
                 │ Shopify Permissions │
                 └──────────┬──────────┘
                            │
                            ▼
                 ┌─────────────────────┐
                 │ Access Token        │
                 │ Stored Securely     │
                 └──────────┬──────────┘
                            │
                            ▼
                 ┌─────────────────────┐
                 │ Shopify Admin APIs  │
                 └─────────────────────┘
```

The application must support:

```text
One Application
      │
      ├── Store A
      │     └── Access Token A
      │
      ├── Store B
      │     └── Access Token B
      │
      ├── Store C
      │     └── Access Token C
      │
      └── Store N
            └── Access Token N
```

Explain clearly how one Shopify App can be installed on multiple Shopify stores and how each store has its own authorization/access token.

---

# 3. Shopify Dev Dashboard App

I have already created a Shopify Dev Dashboard App.

The current permissions/scopes are:

```text
read_customers
read_discounts
write_gift_cards
read_gift_cards
read_inventory
write_orders
read_orders
read_price_rules
read_products
read_shipping
read_content
shop_app:oauth
```

The app also has:

```text
Webhook API Version:
2026-07
```

I will provide:

```text
Client ID
Client Secret
```

Do NOT expose the Client Secret in frontend code, GitHub repositories, JavaScript, HTML, or publicly accessible files.

Use environment variables/configuration outside the public web root where appropriate.

---

# 4. First Explain Shopify Configuration

Before writing application code, explain exactly what I need to configure in Shopify Dev Dashboard.

Specifically explain:

## App URL

Tell me:

* What should be entered as the App URL?
* Give an example for local development.
* Give an example for production.
* Explain whether this URL should point to the application's home/dashboard page.
* Explain HTTPS requirements for production.

Example format:

```text
https://example.com/shopify/
```

---

## Redirect URLs

Explain exactly what I should enter under Shopify's allowed redirect URLs.

For example:

```text
https://example.com/shopify/oauth/callback.php
```

Also explain:

* Why Shopify needs the redirect URL.
* How the OAuth flow uses it.
* Whether multiple redirect URLs can be configured.
* How local development URLs should be handled.
* Whether localhost HTTP URLs are acceptable for development.
* How production redirect URLs should differ from development URLs.

Do not simply provide generic examples. Explain the relationship between:

```text
App URL
OAuth Install URL
OAuth Callback URL
Webhook URLs
```

---

# 5. OAuth Installation Flow

Implement the Shopify OAuth installation process.

The flow should be:

```text
Merchant
   │
   ▼
Open App Install URL
   │
   ▼
Enter/Select Shopify Store
   │
   ▼
Validate Shop Domain
   │
   ▼
Generate OAuth Authorization URL
   │
   ▼
Shopify
   │
   ▼
Merchant Approves Permissions
   │
   ▼
OAuth Callback
   │
   ▼
Validate OAuth Request
   │
   ▼
Exchange Authorization Code
   │
   ▼
Receive Access Token
   │
   ▼
Store Token in MySQL
   │
   ▼
Redirect to Store Dashboard
```

Implement all required security checks, including:

* OAuth `state`
* HMAC validation where applicable
* Shop domain validation
* Redirect URI validation
* Secure access-token storage
* Protection against CSRF
* Protection against malicious/invalid shop parameters
* No client secret exposure

Explain every important step.

---

# 6. Multi-Store Support

This is one of the most important requirements.

The same Shopify App must support multiple Shopify stores.

For example:

```text
Store 1:
mystore-a.myshopify.com

Store 2:
mystore-b.myshopify.com

Store 3:
mystore-c.myshopify.com
```

Each store must have its own:

```text
shop_domain
access_token
scopes
installation information
created_at
updated_at
```

Design the database so that one store's token can never accidentally be used for another store.

Explain:

* How Shopify identifies each store.
* Why `shop_domain` should be unique.
* How to retrieve a store's access token.
* How API calls select the correct store.
* How to uninstall/reinstall a store.
* What happens when a store's access token becomes invalid.
* How to handle token revocation.
* How to prevent cross-store data access.

---

# 7. MySQL Database Design

Create a proper MySQL schema.

At minimum, create a table similar to:

```text
shopify_stores
```

It should contain appropriate fields such as:

```text
id
shop_domain
access_token
scopes
status
installed_at
updated_at
```

You may add additional fields if required.

Also consider tables for:

```text
shopify_webhooks
shopify_products
shopify_orders
oauth_states
```

Only create synchronization tables if they are useful for the sample application.

Explain the purpose of every table and field.

Provide:

1. ER-style relationship explanation
2. SQL `CREATE TABLE` statements
3. Indexes
4. Unique constraints
5. Foreign keys where appropriate

---

# 8. Project Folder Structure

Use a clean PHP application structure.

For example:

```text
shopify-multi-store-app/
│
├── public/
│   ├── index.php
│   ├── install.php
│   ├── oauth/
│   │   └── callback.php
│   ├── dashboard.php
│   ├── stores.php
│   ├── products.php
│   ├── orders.php
│   └── webhooks/
│       └── orders-create.php
│
├── src/
│   ├── Config/
│   ├── Database/
│   ├── Shopify/
│   ├── OAuth/
│   ├── Webhooks/
│   ├── Models/
│   └── Services/
│
├── config/
│
├── vendor/
│
├── .env
├── .gitignore
├── composer.json
└── README.md
```

You may improve this structure if you believe a better architecture is appropriate.

Keep public files separated from sensitive configuration and application logic.

---

# 9. Composer Installation

Show me exactly how to install:

```text
phpclassic/php-shopify
```

using Composer.

Provide the required:

```bash
composer require ...
```

command.

Explain how the autoloader works.

Provide an example of:

```php
require_once __DIR__ . '/../vendor/autoload.php';
```

where appropriate.

---

# 10. Shopify API Service

Create a reusable Shopify service/class.

For example:

```text
ShopifyService
```

The service should allow me to do something like:

```php
$shopify = new ShopifyService($shopDomain);
```

or another clean architecture that you recommend.

The service must:

1. Find the store in MySQL.
2. Retrieve its access token.
3. Initialize `phpclassic/php-shopify`.
4. Configure the Shopify API version.
5. Make API requests.
6. Handle API errors.
7. Prevent one store's token from being used with another store.

Do not duplicate Shopify initialization code throughout the project.

---

# 11. Store Dashboard

After successful installation, show a store-specific dashboard.

Example:

```text
Shopify Multi-Store App

Connected Stores
----------------------------------------
Store A     Connected
Store B     Connected
Store C     Connected

[Add Shopify Store]
```

When selecting a store:

```text
Store:
mystore-a.myshopify.com

Features:
-------------------------
Products
Orders
Customers
Inventory
Webhooks
Store Information
```

All operations must use the selected store's credentials.

---

# 12. Fetch Shopify Store Products

Create functionality to fetch products from the selected Shopify store.

Example:

```text
GET Products
```

Display:

```text
Product ID
Title
Status
Vendor
Product Type
Created Date
Updated Date
```

Use the `phpclassic/php-shopify` library.

Handle:

* Pagination
* API errors
* Empty product lists
* Rate limits
* Invalid access tokens

Use the Shopify Admin REST API only for this project. Do not implement GraphQL queries, mutations, or GraphQL-based services. Before implementation, verify Shopify's current REST Admin API availability, supported API version, and the REST capabilities of `phpclassic/php-shopify`. If Shopify has introduced a REST limitation, explain it clearly and keep the implementation REST-based wherever supported.

---

# 13. Fetch Shopify Store Orders

Create functionality to fetch orders from the selected Shopify store.

Display:

```text
Order ID
Order Number
Financial Status
Fulfillment Status
Total Price
Currency
Created Date
Updated Date
```

Use the appropriate Shopify Admin REST API endpoint through the selected PHP library.

Handle:

* Pagination
* API errors
* Rate limits
* Missing permissions
* Empty results

---

# 14. Order Created Webhook

Create a webhook for:

```text
orders/create
```

The webhook should be created for a specific Shopify store.

For example:

```text
Store A
    └── orders/create webhook

Store B
    └── orders/create webhook
```

The application must be able to create the webhook for each connected store.

Store webhook information in:

```text
shopify_webhooks
```

including appropriate fields such as:

```text
id
shop_id
topic
webhook_id
callback_url
api_version
status
created_at
updated_at
```

---

# 15. Webhook Callback

Create a webhook endpoint such as:

```text
/webhooks/orders-create.php
```

It must:

1. Receive Shopify webhook requests.
2. Read the raw request body.
3. Validate the Shopify webhook HMAC/signature.
4. Reject invalid requests.
5. Parse the JSON payload.
6. Identify the Shopify store.
7. Process the order event.
8. Log appropriate information.
9. Return the correct HTTP response.

Explain webhook security carefully.

The webhook must NOT trust a store ID supplied in the request body if Shopify provides a more reliable authenticated store identifier through webhook headers.

---

# 16. Store-Specific Webhook Management

The dashboard should provide:

```text
Store A
--------------------------------
Orders Create Webhook
Status: Active

[Create Webhook]
[Delete Webhook]
```

The same functionality should work independently for:

```text
Store B
Store C
Store N
```

Explain how the application determines which store's access token must be used when creating or deleting a webhook.

---

# 17. API Version Management

The Dev Dashboard configuration currently specifies:

```text
Webhook API Version: 2026-07
```

Explain:

* What this means.
* Whether the Admin API version should also be explicitly configured.
* How Shopify API versions work.
* Where the API version should be configured in the application.
* How to make API version upgrades easier later.

Do not hard-code the version in many different PHP files.

Use a centralized configuration value.

---

# 18. Security Requirements

Treat this as a production-style learning project.

Implement:

### OAuth Security

* State parameter
* HMAC verification
* Secure callback handling
* Shop domain validation

### Credential Security

* Client Secret must remain server-side
* Access tokens must not be exposed to browser JavaScript
* Do not commit `.env` to Git
* Add `.env` to `.gitignore`

### Database Security

* PDO prepared statements
* No SQL injection
* Proper indexes
* Unique shop domains

### Webhook Security

* Verify webhook HMAC
* Validate request headers
* Handle replay/duplicate events where appropriate

### Application Security

* Input validation
* Output escaping
* CSRF protection for application forms
* Secure session handling
* Error handling without exposing secrets

---

# 19. Error Handling

Create centralized error handling.

Handle at minimum:

```text
Invalid shop
OAuth failed
Invalid state
Invalid HMAC
Access token exchange failed
Invalid access token
Shopify API error
Rate limit
Missing scope
Webhook HMAC failure
Database failure
Network failure
```

Show user-friendly errors while keeping detailed technical errors in server logs.

Never display:

```text
Client Secret
Access Token
Database password
```

in browser output.

---

# 20. Logging

Implement application logging.

Logs should help diagnose:

```text
OAuth installation
OAuth callback
Store connection
API requests/errors
Webhook registration
Webhook callbacks
Webhook failures
Token problems
```

Do not log access tokens, client secrets, or other sensitive credentials.

---

# 21. Uninstall Handling

Explain and implement Shopify App Uninstall handling.

When a store uninstalls the app:

```text
Shopify Store
      │
      ▼
App Uninstalled
      │
      ▼
Webhook/Event
      │
      ▼
Mark Store Inactive
      │
      ▼
Invalidate/Remove Stored Credentials
```

Explain what should happen to:

* Access token
* Store record
* Webhook records
* Local product/order data

Do not assume that deleting the database record is always the best approach; explain the options.

---

# 22. Environment Configuration

Create a `.env` configuration similar to:

```text
SHOPIFY_CLIENT_ID=
SHOPIFY_CLIENT_SECRET=
SHOPIFY_APP_URL=
SHOPIFY_REDIRECT_URI=
SHOPIFY_API_VERSION=
SHOPIFY_SCOPES=

DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

Explain how these values should be configured for:

### Local development

and

### Production

Do not put secrets directly into PHP source code.

---

# 23. Local Development

Explain how I can run this application locally.

For example:

```text
http://localhost/shopify-multi-store-app
```

or using PHP's built-in server:

```bash
php -S localhost:8000 -t public
```

Explain the Shopify requirements for local OAuth callbacks and webhooks.

If HTTPS/public access is required, explain how a development tunnel such as:

```text
ngrok
Cloudflare Tunnel
```

can be used.

Show how the URLs map to:

```text
App URL
OAuth Redirect URL
Webhook URL
```

---

# 24. Production Deployment

Explain how to deploy the application to a normal PHP hosting/server.

Cover:

```text
Apache/Nginx
PHP
MySQL
Composer
HTTPS/SSL
Environment variables
Document root
Cron jobs if needed
Logging
```

Explain how the production URLs should be configured in Shopify Dev Dashboard.

---

# 25. GitHub Configuration

Create a proper `.gitignore`.

It must exclude:

```text
.env
/vendor/
logs/
cache/
temporary files
```

Explain what should and should not be committed.

Also create a professional:

```text
README.md
```

containing:

* Project overview
* Architecture
* Requirements
* Installation
* Composer setup
* MySQL setup
* Shopify Dev Dashboard setup
* OAuth configuration
* App URL configuration
* Redirect URL configuration
* Webhook configuration
* Local development
* Production deployment
* Multi-store architecture
* Security
* API examples
* Troubleshooting

---

# 26. Important Shopify Questions I Need Answered

Before or during implementation, explicitly answer these questions:

### Question 1

Can one Shopify Dev Dashboard App be installed on multiple Shopify stores?

### Question 2

Does each Shopify store get its own access token?

### Question 3

How does my application identify which store is making/receiving a request?

### Question 4

Can Store A's access token ever be used to access Store B?

### Question 5

Where should the access tokens be stored?

### Question 6

Do I need a separate Shopify App for every store?

### Question 7

How do I add a second/third Shopify store?

### Question 8

How do I handle reinstalling the same store?

### Question 9

How do I handle app uninstall?

### Question 10

Can I create different webhooks for each store using the same application?

### Question 11

What exactly should be entered into:

```text
App URL
Allowed Redirect URLs
Webhook URL
```

### Question 12

What is the relationship between:

```text
Client ID
Client Secret
OAuth Authorization Code
Access Token
Shop Domain
```

---

# 27. Expected Multi-Store Architecture

The final application should conceptually work like this:

```text
                    Shopify App
                         │
              ┌──────────┴──────────┐
              │                     │
        OAuth Installation     Store Management
              │                     │
              ▼                     ▼
       ┌──────────────┐      ┌───────────────┐
       │   MySQL      │      │ Store Dashboard│
       └──────┬───────┘      └───────┬───────┘
              │                      │
       ┌──────┼──────────────┐       │
       │      │              │       │
       ▼      ▼              ▼       ▼
    Store A Store B       Store C  Store N
       │      │              │       │
    Token A Token B       Token C Token N
       │      │              │       │
       ▼      ▼              ▼       ▼
    Shopify Shopify       Shopify Shopify
     Store A  Store B      Store C  Store N
```

The architecture must guarantee **store isolation**.

---

# 28. Development Approach

Do NOT generate the entire project in one huge response.

Build it incrementally in the following phases:

## Phase 1 — Architecture & Shopify Configuration

Explain:

* Shopify Dev Dashboard configuration
* App URL
* Redirect URLs
* OAuth flow
* Multi-store architecture
* Required API versions
* Security model

## Phase 2 — PHP Project Setup

Create:

* Folder structure
* Composer
* `.env`
* Configuration
* Database connection
* Basic application

## Phase 3 — MySQL

Create:

* Database
* Tables
* Indexes
* Relationships

## Phase 4 — OAuth

Implement:

* Install endpoint
* Authorization URL
* State
* Callback
* HMAC validation
* Access-token exchange
* Store registration

## Phase 5 — Multi-Store Dashboard

Implement:

* Connected stores
* Add store
* Select store
* Store-specific context

## Phase 6 — Shopify API

Implement:

* ShopifyService
* Product API
* Order API
* Error handling
* Pagination
* Rate-limit handling

## Phase 7 — Webhooks

Implement:

* Create webhook
* List webhook
* Delete webhook
* orders/create
* HMAC verification
* Store identification

## Phase 8 — Uninstall

Implement:

* App uninstall handling
* Token invalidation
* Store status

## Phase 9 — Security Hardening

Review:

* OAuth
* HMAC
* CSRF
* SQL injection
* Secret management
* Token handling
* Webhook security

## Phase 10 — Testing & Documentation

Provide:

* Test cases
* Manual testing steps
* Troubleshooting
* README
* Production deployment instructions

---

# 29. Coding Requirements

When providing code:

1. Give complete files rather than partial snippets whenever practical.
2. Clearly mention the file path before each file.
3. Do not leave unexplained placeholders.
4. Use PHP 8.2+ syntax.
5. Follow PSR-style coding practices.
6. Use classes/services where appropriate.
7. Use PDO prepared statements.
8. Use Composer autoloading.
9. Keep Shopify API logic centralized.
10. Keep database logic separated from presentation.
11. Do not expose secrets.
12. Add useful comments where Shopify-specific behavior may not be obvious.
13. Do not unnecessarily introduce Laravel, Symfony, or another framework.
14. Use plain PHP unless a framework is specifically justified.
15. Keep the project easy to understand for someone learning Shopify app development.

---

# 30. Important Instruction About Shopify API Documentation

Shopify's APIs and authentication mechanisms can change over time.

Before giving implementation instructions for current Shopify APIs, OAuth, Dev Dashboard configuration, access tokens, API versions, or `phpclassic/php-shopify`, verify the current Shopify documentation and the current library documentation/repository.

Do not rely solely on old Shopify tutorials.

If the library's REST implementation differs from Shopify's current REST documentation, clearly explain the difference and use the current compatible REST approach. Do not switch this project to GraphQL.

---

# 31. First Response Requirement

For the first response, DO NOT generate the complete project.

Instead provide:

1. **Overall architecture**
2. **How multi-store Shopify Apps work**
3. **Exact explanation of App URL**
4. **Exact explanation of Redirect URLs**
5. **OAuth flow**
6. **How access tokens are stored per store**
7. **How Store A and Store B are isolated**
8. **Recommended MySQL architecture**
9. **Recommended PHP project structure**
10. **Shopify Dev Dashboard configuration checklist**
11. **Required information/credentials I need to provide**
12. **A Phase 1 implementation plan**

After that, wait for my confirmation before moving to Phase 2.

The goal is to build this application **step by step**, while explaining the Shopify concepts so that I understand the architecture rather than simply copying code.
