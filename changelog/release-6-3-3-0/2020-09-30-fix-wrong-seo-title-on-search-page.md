---
title: Fix wrong seo title on search page
issue: NEXT-10963
author_github: @Dominik28111
---
# Storefront
*  Changed method `load()` in `Cicada\Storefront\Page\GenericPageLoader` to set meta title for current sales channel otherwise it fallbacks to global. 
