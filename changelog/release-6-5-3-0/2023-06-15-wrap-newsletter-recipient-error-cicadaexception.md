---
title: Wrap Newsletter recipient error in CicadaException
issue: NEXT-28566
---
# Storefront
* Added `Cicada\Core\Content\Newsletter\NewsletterException`.
* Changed `\Cicada\Storefront\Controller\NewsletterController::subscribeMail` to catch recipient error and redirect to frontpage.
