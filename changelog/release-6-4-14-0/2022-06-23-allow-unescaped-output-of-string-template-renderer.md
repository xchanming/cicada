---
title: Allow unescaped output of string template renderer
issue: NEXT-19158
author: d.neustadt
author_email: d.neustadt@cicada.com
author_github: dneustadt
---
# Core
* Added optional parameter `htmlEscape` of method `Cicada\Core\Framework\Adapter\Twig\StringTemplateRenderer:render` 
___
# Next Major Version Changes

## Extending `StringTemplateRenderer`

The class `StringTemplateRenderer` should not be extended and will become `final`.
