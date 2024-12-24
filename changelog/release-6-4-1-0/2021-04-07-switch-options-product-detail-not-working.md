---
title: Fix switch options of product detail not working
issue: NEXT-13979
---
# Storefront
* Changed public function `switch` in `Cicada\Storefront\Controller\ProductController.php` to ensure always the default variant is as fallback available.
