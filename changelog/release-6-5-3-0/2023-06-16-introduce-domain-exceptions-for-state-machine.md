---
title: Introduce domain exception for state machine
issue: NEXT-28609
author: Michel Bade
author_email: m.bade@cicada.com
author_github: @cyl3x
---
# Core
* Deprecated the following exceptions in replacement for Domain Exceptions
    * `Cicada\Core\System\StateMachine\Exception\StateMachineInvalidEntityIdException`
    * `Cicada\Core\System\StateMachine\Exception\StateMachineInvalidStateFieldException`
    * `Cicada\Core\System\StateMachine\Exception\StateMachineNotFoundException`
    * `Cicada\Core\System\StateMachine\Exception\StateMachineStateNotFoundException`
    * `Cicada\Core\System\StateMachine\Exception\StateMachineWithoutInitialStateException`
