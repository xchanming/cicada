---
title: Fix using standard symfony rate limiter on cicada default definitions
issue: NEXT-22045
author: Stephan Niewerth
author_email: snw@heise.de
author_github: stephanniewerth
---
# Core
* Additionally filters the `limits` configuration key when using a symfony standard rate limiter
