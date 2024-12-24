<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Gateway\Command\Executor;

use Cicada\Core\Checkout\Gateway\CheckoutGatewayException;
use Cicada\Core\Checkout\Gateway\CheckoutGatewayResponse;
use Cicada\Core\Checkout\Gateway\Command\CheckoutGatewayCommandCollection;
use Cicada\Core\Checkout\Gateway\Command\Registry\CheckoutGatewayCommandRegistry;
use Cicada\Core\Framework\Log\ExceptionLogger;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
final class CheckoutGatewayCommandExecutor
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CheckoutGatewayCommandRegistry $registry,
        private readonly ExceptionLogger $logger,
    ) {
    }

    public function execute(
        CheckoutGatewayCommandCollection $commands,
        CheckoutGatewayResponse $response,
        SalesChannelContext $context,
    ): CheckoutGatewayResponse {
        foreach ($commands as $command) {
            if (!$this->registry->has($command::getDefaultKeyName())) {
                $this->logger->logOrThrowException(CheckoutGatewayException::handlerNotFound($command::getDefaultKeyName()));
                continue;
            }

            $this->registry->get($command::getDefaultKeyName())->handle($command, $response, $context);
        }

        return $response;
    }
}
