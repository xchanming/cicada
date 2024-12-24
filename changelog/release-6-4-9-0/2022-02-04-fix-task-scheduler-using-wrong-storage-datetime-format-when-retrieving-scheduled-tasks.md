---
title: Fix task scheduler using wrong storage DateTime format when retrieving scheduled tasks.
issue: NEXT-19989
author: Andreas Allacher
author_email: andreas.allacher@massiveart.com
author_github: @AndreasA
---
# Core
* Changed `\Cicada\Core\Framework\MessageQueue\ScheduledTask\Scheduler\TaskScheduler` to use `\Cicada\Core\Defaults::STORAGE_DATE_TIME_FORMAT` when retrieving scheduled tasks.
