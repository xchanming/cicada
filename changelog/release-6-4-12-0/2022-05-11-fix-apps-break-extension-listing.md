---
title: Fix apps breaking extension listing in administration
issue: NEXT-21573
author: Silvio Kennecke
author_github: @silviokennecke
---
# Core
* Changed `\Cicada\Core\Framework\Store\Services\ExtensionLoader::prepareAppData` to load app label and description from translations
