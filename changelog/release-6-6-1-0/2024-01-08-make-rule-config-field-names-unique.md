---
title: Make `RuleConfig` field names unique
issue: NEXT-33774
author: Max
author_email: max@swk-web.com
author_github: @aragon999
---
# Core
* Changed behaviour of `Cicada\Core\Framework\Rule\RuleConfig` to make sure that field names are unique
* Added method `Cicada\Core\Framework\Rule\RuleConfig::getField` to fetch a field by its name
