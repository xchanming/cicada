<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Checkout\Customer\Event\CustomerChangedPaymentMethodEvent;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Exception\InvalidUuidException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SuccessResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @deprecated tag:v6.7.0 - will be removed, customer has no default payment method anymore
 */
#[Route(defaults: ['_routeScope' => ['store-api'], '_contextTokenRequired' => true])]
#[Package('checkout')]
class ChangePaymentMethodRoute extends AbstractChangePaymentMethodRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $customerRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityRepository $paymentMethodRepository
    ) {
    }

    public function getDecorated(): AbstractChangePaymentMethodRoute
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'customer has no default payment method anymore');

        throw new DecorationPatternException(self::class);
    }

    /**
     * @deprecated tag:v6.7.0 - will be removed, customer has no default payment method anymore
     */
    #[Route(path: '/store-api/account/change-payment-method/{paymentMethodId}', name: 'store-api.account.set.payment-method', methods: ['POST'], defaults: ['_loginRequired' => true])]
    public function change(string $paymentMethodId, RequestDataBag $requestDataBag, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'customer has no default payment method anymore');

        $this->validatePaymentMethodId($paymentMethodId, $context->getContext());

        $this->customerRepository->update([
            [
                'id' => $customer->getId(),
                'defaultPaymentMethodId' => $paymentMethodId,
            ],
        ], $context->getContext());

        $event = new CustomerChangedPaymentMethodEvent($context, $customer, $requestDataBag);
        $this->eventDispatcher->dispatch($event);

        return new SuccessResponse();
    }

    /**
     * @throws InvalidUuidException
     */
    private function validatePaymentMethodId(string $paymentMethodId, Context $context): void
    {
        if (!Uuid::isValid($paymentMethodId)) {
            throw new InvalidUuidException($paymentMethodId);
        }

        /** @var PaymentMethodEntity|null $paymentMethod */
        $paymentMethod = $this->paymentMethodRepository->search(new Criteria([$paymentMethodId]), $context)->get($paymentMethodId);

        if (!$paymentMethod) {
            throw CustomerException::unknownPaymentMethod($paymentMethodId);
        }
    }
}
