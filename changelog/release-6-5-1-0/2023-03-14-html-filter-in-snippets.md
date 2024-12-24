---
title: HTML Filter in Snippets
issue: NEXT-25711
---
# Core
* Changed method `getConfig` in `src/Core/Framework/Util/HtmlSanitizer.php` service to allow target and rel attributes
* Added new field `snippet.value` in `cicada.html_sanitizer` to allow `<img>` element in snippet
