<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Checkout\Gateway;

use Cicada\Core\Checkout\Gateway\CheckoutGatewayException;
use Cicada\Core\Checkout\Gateway\CheckoutGatewayInterface;
use Cicada\Core\Checkout\Gateway\CheckoutGatewayResponse;
use Cicada\Core\Checkout\Gateway\Command\AbstractCheckoutGatewayCommand;
use Cicada\Core\Checkout\Gateway\Command\CheckoutGatewayCommandCollection;
use Cicada\Core\Checkout\Gateway\Command\Event\CheckoutGatewayCommandsCollectedEvent;
use Cicada\Core\Checkout\Gateway\Command\Executor\CheckoutGatewayCommandExecutor;
use Cicada\Core\Checkout\Gateway\Command\Registry\CheckoutGatewayCommandRegistry;
use Cicada\Core\Checkout\Gateway\Command\Struct\CheckoutGatewayPayloadStruct;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\App\ActiveAppsLoader;
use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\Checkout\Payload\AppCheckoutGatewayPayload;
use Cicada\Core\Framework\App\Checkout\Payload\AppCheckoutGatewayPayloadService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Cicada\Core\Framework\Log\ExceptionLogger;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal only for use by the app-system
 */
#[Package('checkout')]
class AppCheckoutGateway implements CheckoutGatewayInterface
{
    /**
     * @param EntityRepository<AppCollection> $appRepository
     *
     * @internal
     */
    public function __construct(
        private readonly AppCheckoutGatewayPayloadService $payloadService,
        private readonly CheckoutGatewayCommandExecutor $executor,
        private readonly CheckoutGatewayCommandRegistry $registry,
        private readonly EntityRepository $appRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ExceptionLogger $logger,
        private readonly ActiveAppsLoader $activeAppsLoader
    ) {
    }

    public function process(CheckoutGatewayPayloadStruct $payload): CheckoutGatewayResponse
    {
        $collected = new CheckoutGatewayCommandCollection();

        $context = $payload->getSalesChannelContext();
        $paymentMethods = $payload->getPaymentMethods()->map(fn (PaymentMethodEntity $paymentMethod) => $paymentMethod->getTechnicalName());
        $shippingMethods = $payload->getShippingMethods()->map(fn (ShippingMethodEntity $shippingMethod) => $shippingMethod->getTechnicalName());

        $appPayload = new AppCheckoutGatewayPayload($context, $payload->getCart(), $paymentMethods, $shippingMethods);
        $apps = $this->getActiveAppsWithCheckoutGateway($context->getContext());

        foreach ($apps as $app) {
            /** @var string $checkoutGatewayUrl */
            $checkoutGatewayUrl = $app->getCheckoutGatewayUrl();
            $appResponse = $this->payloadService->request($checkoutGatewayUrl, $appPayload, $app);

            if (!$appResponse) {
                $this->logger->logOrThrowException(CheckoutGatewayException::emptyAppResponse($app->getName()));
                continue;
            }

            $this->collectCommandsFromAppResponse($appResponse, $collected);
        }

        $response = new CheckoutGatewayResponse(
            $payload->getPaymentMethods(),
            $payload->getShippingMethods(),
            $payload->getCart()->getErrors()
        );

        $this->eventDispatcher->dispatch(new CheckoutGatewayCommandsCollectedEvent($payload, $collected));

        return $this->executor->execute($collected, $response, $context);
    }

    private function getActiveAppsWithCheckoutGateway(Context $context): AppCollection
    {
        // If no active apps are available, we can return early
        if ($this->activeAppsLoader->getActiveApps() === []) {
            return new AppCollection();
        }

        $criteria = new Criteria();
        $criteria->addAssociation('paymentMethods');

        $criteria->addFilter(
            new EqualsFilter('active', true),
            new NotFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('checkoutGatewayUrl', null),
            ]),
        );

        return $this->appRepository->search($criteria, $context)->getEntities();
    }

    private function collectCommandsFromAppResponse(AppCheckoutGatewayResponse $commands, CheckoutGatewayCommandCollection $collected): void
    {
        foreach ($commands->getCommands() as $payload) {
            if (!isset($payload['command'], $payload['payload'])) {
                $this->logger->logOrThrowException(CheckoutGatewayException::payloadInvalid($payload['command'] ?? null));

                continue;
            }

            $commandKey = $payload['command'];

            if (!$this->registry->hasAppCommand($commandKey)) {
                $this->logger->logOrThrowException(CheckoutGatewayException::handlerNotFound($commandKey));

                continue;
            }

            $command = $this->registry->getAppCommand($commandKey);

            if (!\is_a($command, AbstractCheckoutGatewayCommand::class, true)) {
                $this->logger->logOrThrowException(CheckoutGatewayException::handlerNotFound($commandKey));

                continue;
            }

            $commandPayload = $payload['payload'];

            try {
                $executableCommand = $command::createFromPayload($commandPayload);
            } catch (\Error) {
                $this->logger->logOrThrowException(CheckoutGatewayException::payloadInvalid($payload['command']));
                continue;
            }

            $collected->add($executableCommand);
        }
    }
}
