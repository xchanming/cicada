---
title: Fix-ContextSwitchRoute-decoration-inheritance
issue: NEXT-15203
author: Jonas Søndergaard
author_email: jonas@wexo.dk 
author_github: Josniii
---
# Core
*  Changed constructor of \Cicada\Core\System\SalesChannel\SalesChannel\SalesChannelContextSwitcher to use abstract class to allow decorators to inherit \Cicada\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute
