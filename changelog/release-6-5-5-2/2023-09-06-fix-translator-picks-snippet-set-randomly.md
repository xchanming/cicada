---
title: Fix translator picks snippet set randomly
issue: NEXT-30335
---
# Core
* Changed `\Cicada\Core\Framework\Adapter\Translation\Translator::getSnippetSetId` to prioritize snippet set id from request in case there're multiple sets matched with current locale
