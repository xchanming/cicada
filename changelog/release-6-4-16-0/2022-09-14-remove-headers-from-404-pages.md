---
title: Remove HTTP-headers also from 404 and error pages
issue: NEXT-21137
---
# Storefront
* Changed `\Cicada\Storefront\Framework\Routing\ResponseHeaderListener` to also remove the headers from 404 and error pages.
