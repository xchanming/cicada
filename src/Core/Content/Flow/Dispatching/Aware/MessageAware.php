<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Aware;

use Cicada\Core\Framework\Event\IsFlowEventAware;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Mime\Email;

#[Package('services-settings')]
#[IsFlowEventAware]
interface MessageAware
{
    public const MESSAGE = 'message';

    public function getMessage(): Email;
}
