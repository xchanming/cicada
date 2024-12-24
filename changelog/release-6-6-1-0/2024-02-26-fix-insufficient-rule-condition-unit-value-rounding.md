---
title: fix insufficient rule condition unit value rounding
issue: NEXT-31729
author: Lars Kemper
author_email: l.kemper@cicada.com
author_github: @LarsKemper
---
# Core
* Added `RuleConfig::DEFAULT_DIGITS` constant to `Cicada\Core\Framework\Rule\RuleConfig.php`
* Changed `numberField()` method in `Cicada\Core\Framework\Rule\RuleConfig.php` to increase the default number field digits.
