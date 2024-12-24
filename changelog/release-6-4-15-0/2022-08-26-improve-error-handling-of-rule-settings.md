---
title: Improve error handling of rule settings
issue: NEXT-22997
author: Sebastian Seggewiss
author_email: s.seggewiss@cicada.com
author_github: @seggewiss
---
# Core
* Changed field `type` of Definition `\Cicada\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionDefinition` to be required
* Changed `\Cicada\Core\Content\Rule\RuleValidator` does no longer check for the condition type to be empty
