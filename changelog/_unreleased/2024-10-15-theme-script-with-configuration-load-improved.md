---
title: Fix JS script loading of child themes in the Storefront
issue: NEXT-39272
author: Raffaele Carelle
author_email: raffaele.carelle@gmail.com
author_github: @raffaelecarelle
---
# Storefront
* Changed `Cicada\Storefront\Theme\ThemeScripts` component to use `Cicada\Storefront\Theme\ConfigLoader\AbstractConfigLoader` to load theme configuration.
