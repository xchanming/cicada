<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\Event\BeforeLineItemQuantityChangedEvent;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\LineItemFactoryInterface;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
#[Package('checkout')]
class LineItemFactoryRegistry
{
    private readonly DataValidationDefinition $validatorDefinition;

    /**
     * @param LineItemFactoryInterface[]|iterable $handlers
     *
     * @internal
     */
    public function __construct(
        private readonly iterable $handlers,
        private readonly DataValidator $validator,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
        $this->validatorDefinition = $this->createValidatorDefinition();
    }

    /**
     * @param array<string|int, mixed> $data
     */
    public function create(array $data, SalesChannelContext $context): LineItem
    {
        if (!isset($data['id'])) {
            $data['id'] = Uuid::randomHex();
        }

        $this->validate($data);

        $handler = $this->getHandler($data['type'] ?? '');

        $lineItem = $handler->create($data, $context);
        $lineItem->markModified();

        return $lineItem;
    }

    /**
     * @param array<string|int, mixed> $data
     */
    public function update(Cart $cart, array $data, SalesChannelContext $context): void
    {
        $identifier = $data['id'];

        if (!$lineItem = $cart->getLineItems()->get($identifier)) {
            throw CartException::lineItemNotFound($identifier ?? '');
        }

        $this->updateLineItem($cart, $data, $lineItem, $context);
    }

    /**
     * @param array<string|int, mixed> $data
     */
    public function updateLineItem(Cart $cart, array $data, LineItem $lineItem, SalesChannelContext $context): void
    {
        if (!isset($data['type'])) {
            $data['type'] = $lineItem->getType();
        }

        $this->validate($data);

        $handler = $this->getHandler($data['type'] ?? '');

        if (isset($data['quantity'])) {
            $beforeUpdateQuantity = $lineItem->getQuantity();

            $lineItem->setQuantity($data['quantity']);

            if (Feature::isActive('v6.7.0.0')) {
                $event = new BeforeLineItemQuantityChangedEvent($lineItem, $cart, $context, $beforeUpdateQuantity);
            } else {
                $event = new BeforeLineItemQuantityChangedEvent($lineItem, $cart, $context);
                Feature::callSilentIfInactive('v6.7.0.0', fn () => $event->setBeforeUpdateQuantity($beforeUpdateQuantity));
            }

            $this->eventDispatcher->dispatch($event);
        }

        $lineItem->markModified();

        $handler->update($lineItem, $data, $context);
    }

    private function getHandler(string $type): LineItemFactoryInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($type)) {
                return $handler;
            }
        }

        throw CartException::lineItemTypeNotSupported($type);
    }

    /**
     * @param array<string|int, mixed> $data
     */
    private function validate(array $data): void
    {
        $this->validator->validate($data, $this->validatorDefinition);
    }

    private function createValidatorDefinition(): DataValidationDefinition
    {
        return (new DataValidationDefinition())
            ->add('id', new Type('string'), new Required())
            ->add('type', new Type('string'), new Required())
            ->add('quantity', new Type('int'))
            ->add('payload', new Type('array'))
            ->add('stackable', new Type('bool'))
            ->add('removable', new Type('bool'))
            ->add('label', new Type('string'))
            ->add('referencedId', new Type('string'))
            ->add('coverId', new Type('string'), new EntityExists(['entity' => MediaDefinition::ENTITY_NAME, 'context' => Context::createDefaultContext()]))
            ->addSub(
                'priceDefinition',
                (new DataValidationDefinition())
                    ->add('type', new Type('string'))
                    ->add('price', new Type('numeric'))
                    ->add('percentage', new Type('numeric'))
                    ->add('quantity', new Type('int'))
                    ->add('isCalculated', new Type('bool'))
                    ->add('listPrice', new Type('numeric'))
                    ->addList(
                        'taxRules',
                        (new DataValidationDefinition())
                            ->add('taxRate', new Type('numeric'))
                            ->add('percentage', new Type('numeric'))
                    )
            );
    }
}
