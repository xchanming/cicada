<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Subscriber;

use Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeCollection;
use Cicada\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeEntity;
use Cicada\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Cicada\Core\Checkout\Promotion\PromotionException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('buyers-experience')]
class PromotionIndividualCodeRedeemer implements EventSubscriberInterface
{
    /**
     * @internal
     *
     * @param EntityRepository<PromotionIndividualCodeCollection> $codesRepository
     */
    public function __construct(private readonly EntityRepository $codesRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }

    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        foreach ($event->getOrder()->getLineItems() ?? [] as $item) {
            // only update promotions in here
            if ($item->getType() !== PromotionProcessor::LINE_ITEM_TYPE) {
                continue;
            }

            /** @var string $code */
            $code = $item->getPayload()['code'] ?? '';

            try {
                // first try if its an individual
                // if not, then it might be a global promotion
                $individualCode = $this->getIndividualCode($code, $event->getContext());
            } catch (PromotionException) {
                $individualCode = null;
            }

            // if we did not use an individual code we might have
            // just used a global one or anything else, so just continue in this case
            // and go on with the next promotion if any are left in the collection
            if (!($individualCode instanceof PromotionIndividualCodeEntity)) {
                continue;
            }

            /** @var OrderCustomerEntity $customer */
            $customer = $event->getOrder()->getOrderCustomer();

            // set the code to be redeemed
            // and assign all required meta data
            // for later needs
            $individualCode->setRedeemed(
                $item->getOrderId(),
                $customer->getCustomerId() ?? '',
                $customer->getName()
            );

            // save in database
            $this->codesRepository->update(
                [
                    [
                        'id' => $individualCode->getId(),
                        'payload' => $individualCode->getPayload(),
                    ],
                ],
                $event->getContext()
            );
        }
    }

    private function getIndividualCode(string $code, Context $context): PromotionIndividualCodeEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('code', $code)
        );

        /** @var PromotionIndividualCodeEntity|null $promotion */
        $promotion = $this->codesRepository->search($criteria, $context)->first();

        if (!$promotion) {
            throw PromotionException::promotionCodeNotFound($code);
        }

        return $promotion;
    }
}
