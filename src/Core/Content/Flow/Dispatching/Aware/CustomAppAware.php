<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Aware;

use Cicada\Core\Framework\Event\IsFlowEventAware;
use Cicada\Core\Framework\Log\Package;

#[Package('after-sales')]
#[IsFlowEventAware]
interface CustomAppAware
{
    public const CUSTOM_DATA = 'customAppData';

    /**
     * @return array<string, mixed>|null
     */
    public function getCustomAppData(): ?array;
}
