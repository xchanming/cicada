---
title: Fix excluded search terms with capital letters do not work
issue: NEXT-15786
---
# Core
* Changed getConfig function on Cicada\Core\Framework\DataAbstractionLayer\Search\Term\Filter\TokenFilter class to convert all excluded search terms to lowercase before being filtered.
