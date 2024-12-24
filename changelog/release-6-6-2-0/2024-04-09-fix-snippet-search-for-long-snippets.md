---
title: Fix snippet search for very long snippets
issue: NEXT-34419
author: Altay Akkus
author_email: altayakkus1993@gmail.com
author_github: @AltayAkkus
---
# Core
* Changed `Cicada\Core\System\Snippet\Filter\TermFilter` and `Cicada\Core\System\Snippet\Filter\NamespaceFilter` so they can handle large snippets.
* Deprecated `Cicada\Core\System\Snippet\Exception\FilterNotFoundException`, which will be removed in v6.7.0.0. Use `Cicada\Core\System\Snippet\SnippetException::filterNotFound` instead.
* Deprecated `Cicada\Core\System\Snippet\Exception\InvalidSnippetFileException`, which will be removed in v6.7.0.0. Use `Cicada\Core\System\Snippet\SnippetException::invalidSnippetFile` instead.
___
# Next Major Version Changes
## Removal of deprecated exceptions
* Removed `Cicada\Core\System\Snippet\Exception\FilterNotFoundException`. Use `Cicada\Core\System\Snippet\SnippetException::filterNotFound` instead.
* Removed `Cicada\Core\System\Snippet\Exception\InvalidSnippetFileException`. Use `Cicada\Core\System\Snippet\SnippetException::invalidSnippetFile` instead.
