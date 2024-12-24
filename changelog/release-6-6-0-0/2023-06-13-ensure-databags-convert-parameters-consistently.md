---
title: Ensure databags convert parameters consistently
issue: NEXT-31821
author: Joshua Behrens
author_email: code@joshua-behrens.de
author_github: @JoshuaBehrens
---
# Core
* Changed `\Cicada\Core\Framework\Validation\DataBag\DataBag::set` and `\Cicada\Core\Framework\Validation\DataBag\DataBag::add` to convert arrays to `\Cicada\Core\Framework\Validation\DataBag\DataBag` like the constructor
* Added `\Cicada\Core\Framework\Validation\DataBag\DataBag::__clone` to deep clone
