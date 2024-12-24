---
title: Fix typo in API expectation error code
author: Joshua Behrens
issue: NEXT-24924
author_email: code@joshua-behrens.de
author_github: @JoshuaBehrens
---
# Core
* Changed constant return value of `\Cicada\Core\Framework\Api\Exception\ExceptionFailedException::getErrorCode` from `FRAMEWORK__API_EXCEPTION_FAILED` to `FRAMEWORK__API_EXPECTATION_FAILED`
* Deprecated `\Cicada\Core\Framework\Api\Exception\ExceptionFailedException` and added `\Cicada\Core\Framework\Api\Exception\ExpectationFailedException` to replace it
