---
title: Remove deprecation of AppSystemTestBehaviour
issue: NEXT-39224
author: Oliver Skroblin
author_email: oliver@goblin-coders.de
author_github: OliverSkroblin
---
# Core
* Removed `@depracted` flag from `Cicada\Tests\Integration\Core\AppSystemTestBehaviour.php`
* Changed path from `Cicada\Tests\Integration\Core\Framework\App` to `Cicada\Core\Test` allowing its consumption
* Changed all tests consuming this trait
