---
title: Add redirectParameters to confirmUrl
issue: NEXT-31466 
author: Benny Poensgen
author_email: poensgen@vanwittlaer.de
author_github: vanwittlaer
---
# Core
* Changed `Cicada\Core\Checkout\Customer\SalesChannel\RegisterRoute` to take care of redirectParameters and include them in the emitted double-opt-in event
___
# Storefront
* Changed `Cicada\Storefront\Controller\RegisterController::confirmRegistration` to include redirectParameters in the actual redirect
