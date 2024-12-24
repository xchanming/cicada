---
title: Remove entity structure in landing page
issue: NEXT-20646
---
# Storefront
* Deprecated `cmsPage` and corresponding methods in `Cicada\Storefront\Page\LandingPage\LandingPage` use `LandingPage::getLandingPage()::getCmsPage()` instead.
* Deprecated `customFields` and corresponding methods in `Cicada\Storefront\Page\LandingPage\LandingPage` use `LandingPage::getLandingPage()::getCustomFields()` instead.
* Deprecated `EntityCustomFieldsTrait` in `Cicada\Storefront\Page\LandingPage\LandingPage`.
* Added `landingPage` in `Cicada\Storefront\Page\LandingPage\LandingPage`
* Added `landingPage` variable to `storefront/page/content/detail.html.twig`
