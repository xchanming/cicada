---
title: Fix conflict between same filenames during build
issue: NEXT-39001
author: Björn Meyer
author_email: b.meyer@cicada.com
author_github: @BrocksiNet
---
# Storefront
* Changed `FilenameToChunkNamePlugin` to use `chunk.name` instead of `chunk.id` (always empty)
