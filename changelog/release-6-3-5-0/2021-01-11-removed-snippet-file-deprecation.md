---
title: remove snippet file deprecation
issue: NEXT-5791
---
# Core
* Removed the deprecation on the `\Cicada\Core\System\Snippet\Files\SnippetFileInterface`-class, as removing the snippet loading over php files would unnecessarily break existing plugins.
