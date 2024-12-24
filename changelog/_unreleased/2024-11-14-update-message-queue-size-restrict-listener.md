---
title: Change behaviour of MessageQueueSizeRestrictListener
issue: NEXT-39611
author: Benjamin Wittwer
author_email: benjamin.wittwer@a-k-f.de
author_github: akf-bw
---
# Core
* Changed behaviour of `\Cicada\Core\Framework\MessageQueue\Subscriber\MessageQueueSizeRestrictListener` to directly skip all checks, if config value `cicada.messenger.enforce_message_size` is set to `false` to prevent calculation time for large messages
