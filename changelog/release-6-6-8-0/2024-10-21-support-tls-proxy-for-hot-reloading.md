---
title: Support TLS proxy for hot reloading
issue: NEXT-37871
author: Björn Meyer
author_email: b.meyer@cicada.com
author_github: @BrocksiNet
---
# Storefront
* Changed the `start-hot-reload.js` script to support TLS proxy for hot reloading.
___
# Upgrade Information
If you use a TLS proxy in your setup, you can now start the hot reloading with https without setting certificate files.

**_Example .env file for a DDEV setup:_**
```
IPV4FIRST=1
APP_ENV=dev
HOST=0.0.0.0
STOREFRONT_ASSETS_PORT=9999
STOREFRONT_PROXY_PORT=9998
APP_URL=https://cicada-ddev-new.ddev.site/
PROXY_URL=https://cicada-ddev-new.ddev.site:9998/
STOREFRONT_SKIP_SSL_CERT=true
```
