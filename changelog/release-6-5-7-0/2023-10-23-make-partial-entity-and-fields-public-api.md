---
title: Make PartialEntity and Criteria fields public API
author: Joshua Behrens
author_email: code@joshua-behrens.de
author_github: @JoshuaBehrens
issue: NEXT-31262
---
# Core
* Removed `internal` PHP docs from `\Cicada\Core\Framework\DataAbstractionLayer\PartialEntity`, `\Cicada\Core\Framework\DataAbstractionLayer\Event\PartialEntityLoadedEvent`, `\Cicada\Core\System\SalesChannel\Entity\PartialSalesChannelEntityLoadedEvent`, `\Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria::addFields` and `\Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria::getFields`
