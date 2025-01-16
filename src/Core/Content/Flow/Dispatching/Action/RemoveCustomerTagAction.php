<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Action;

use Cicada\Core\Content\Flow\Dispatching\DelayableAction;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('after-sales')]
class RemoveCustomerTagAction extends FlowAction implements DelayableAction
{
    /**
     * @internal
     */
    public function __construct(private readonly EntityRepository $customerTagRepository)
    {
    }

    public static function getName(): string
    {
        return 'action.remove.customer.tag';
    }

    /**
     * @return array<int, string>
     */
    public function requirements(): array
    {
        return [CustomerAware::class];
    }

    public function handleFlow(StorableFlow $flow): void
    {
        if (!$flow->hasData(CustomerAware::CUSTOMER_ID)) {
            return;
        }

        $this->update($flow->getContext(), $flow->getConfig(), $flow->getData(CustomerAware::CUSTOMER_ID));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function update(Context $context, array $config, string $customerId): void
    {
        if (!\array_key_exists('tagIds', $config)) {
            return;
        }

        $tagIds = array_keys($config['tagIds']);

        if (empty($tagIds)) {
            return;
        }

        $tags = array_map(static fn ($tagId) => [
            'customerId' => $customerId,
            'tagId' => $tagId,
        ], $tagIds);

        $this->customerTagRepository->delete($tags, $context);
    }
}
