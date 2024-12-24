---
title: Fix Create API for Mapping Definitions
issue: NEXT-15661
---
# Core
* Changed `\Cicada\Core\Framework\Api\Controller\ApiController::write()` to return 204-Response without redirect header for mapping definitions, as there is no detail route for mapping definitions.
