<?php declare(strict_types=1);

namespace Cicada\Core\Service\MessageHandler;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Service\Message\UpdateServiceMessage;
use Cicada\Core\Service\ServiceLifecycle;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('core')]
#[AsMessageHandler]
final readonly class UpdateServiceHandler
{
    public function __construct(private ServiceLifecycle $serviceLifecycle)
    {
    }

    public function __invoke(UpdateServiceMessage $updateServiceMessage): void
    {
        $this->serviceLifecycle->update($updateServiceMessage->name, Context::createDefaultContext());
    }
}
