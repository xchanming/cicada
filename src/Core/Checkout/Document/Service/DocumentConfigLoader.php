<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Service;

use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigCollection;
use Cicada\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigEntity;
use Cicada\Core\Checkout\Document\DocumentConfiguration;
use Cicada\Core\Checkout\Document\DocumentConfigurationFactory;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ResetInterface;

#[Package('checkout')]
final class DocumentConfigLoader implements EventSubscriberInterface, ResetInterface
{
    /**
     * @var array<string, array<string, DocumentConfiguration>>
     */
    private array $configs = [];

    /**
     * @internal
     */
    public function __construct(private readonly EntityRepository $documentConfigRepository)
    {
    }

    /**
     * @internal
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'document_base_config.written' => 'reset',
        ];
    }

    public function load(string $documentType, string $salesChannelId, Context $context): DocumentConfiguration
    {
        if (!empty($this->configs[$documentType][$salesChannelId])) {
            return $this->configs[$documentType][$salesChannelId];
        }

        $criteria = new Criteria();

        $criteria->addFilter(new EqualsFilter('documentType.technicalName', $documentType));
        $criteria->addAssociation('logo');
        $criteria->getAssociation('salesChannels')->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));

        /** @var DocumentBaseConfigCollection $documentConfigs */
        $documentConfigs = $this->documentConfigRepository->search($criteria, $context)->getEntities();

        $globalConfig = $documentConfigs->filterByProperty('global', true)->first();

        $salesChannelConfig = $documentConfigs->filter(fn (DocumentBaseConfigEntity $config) => $config->getSalesChannels()->count() > 0)->first();

        $config = DocumentConfigurationFactory::createConfiguration([], $globalConfig, $salesChannelConfig);

        $this->configs[$documentType] ??= [];

        return $this->configs[$documentType][$salesChannelId] = $config;
    }

    /**
     * @internal
     */
    public function reset(): void
    {
        $this->configs = [];
    }
}
