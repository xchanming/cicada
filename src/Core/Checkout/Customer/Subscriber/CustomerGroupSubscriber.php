<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Subscriber;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroupTranslation\CustomerGroupTranslationCollection;
use Cicada\Core\Content\Seo\SeoUrlPersister;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NandFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\LanguageEntity;
use Cocur\Slugify\SlugifyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerGroupSubscriber implements EventSubscriberInterface
{
    private const ROUTE_NAME = 'frontend.account.customer-group-registration.page';

    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $customerGroupRepository,
        private readonly EntityRepository $seoUrlRepository,
        private readonly EntityRepository $languageRepository,
        private readonly SeoUrlPersister $persister,
        private readonly SlugifyInterface $slugify
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'customer_group_translation.written' => 'updatedCustomerGroup',
            'customer_group_registration_sales_channels.written' => 'newSalesChannelAddedToCustomerGroup',
            'customer_group_translation.deleted' => 'deleteCustomerGroup',
        ];
    }

    public function newSalesChannelAddedToCustomerGroup(EntityWrittenEvent $event): void
    {
        $ids = [];

        foreach ($event->getWriteResults() as $writeResult) {
            /** @var array<string, string> $pk */
            $pk = $writeResult->getPrimaryKey();
            $ids[] = $pk['customerGroupId'];
        }

        if (\count($ids) === 0) {
            return;
        }

        $this->createUrls($ids, $event->getContext());
    }

    public function updatedCustomerGroup(EntityWrittenEvent $event): void
    {
        $ids = [];

        foreach ($event->getWriteResults() as $writeResult) {
            if ($writeResult->hasPayload('registrationTitle')) {
                /** @var array<string, string> $pk */
                $pk = $writeResult->getPrimaryKey();
                $ids[] = $pk['customerGroupId'];
            }
        }

        if (\count($ids) === 0) {
            return;
        }

        $this->createUrls($ids, $event->getContext());
    }

    public function deleteCustomerGroup(EntityDeletedEvent $event): void
    {
        $ids = [];

        foreach ($event->getWriteResults() as $writeResult) {
            /** @var array<string, string> $pk */
            $pk = $writeResult->getPrimaryKey();
            $ids[] = $pk['customerGroupId'];
        }

        if (\count($ids) === 0) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('foreignKey', $ids));
        $criteria->addFilter(new EqualsFilter('routeName', self::ROUTE_NAME));

        /** @var array<string> $ids */
        $ids = array_values($this->seoUrlRepository->searchIds($criteria, $event->getContext())->getIds());

        if (\count($ids) === 0) {
            return;
        }

        $this->seoUrlRepository->delete(array_map(fn (string $id) => ['id' => $id], $ids), $event->getContext());
    }

    /**
     * @param list<string> $ids
     */
    private function createUrls(array $ids, Context $context): void
    {
        $criteria = new Criteria($ids);
        $criteria->addFilter(new EqualsFilter('registrationActive', true));

        $criteria->addAssociation('registrationSalesChannels.languages');
        $criteria->addAssociation('translations');

        $criteria->getAssociation('registrationSalesChannels')->addFilter(
            new NandFilter([new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_API)])
        );

        /** @var CustomerGroupCollection $groups */
        $groups = $this->customerGroupRepository->search($criteria, $context)->getEntities();
        $buildUrls = [];

        foreach ($groups as $group) {
            if ($group->getRegistrationSalesChannels() === null) {
                continue;
            }

            foreach ($group->getRegistrationSalesChannels() as $registrationSalesChannel) {
                if ($registrationSalesChannel->getLanguages() === null) {
                    continue;
                }

                if ($registrationSalesChannel->getTypeId() === Defaults::SALES_CHANNEL_TYPE_API) {
                    continue;
                }

                /** @var array<string> $languageIds */
                $languageIds = $registrationSalesChannel->getLanguages()->getIds();
                $criteria = new Criteria($languageIds);
                /** @var LanguageCollection $languageCollection */
                $languageCollection = $this->languageRepository->search($criteria, $context)->getEntities();

                foreach ($languageIds as $languageId) {
                    /** @var LanguageEntity $language */
                    $language = $languageCollection->get($languageId);
                    $title = $this->getTranslatedTitle($group->getTranslations(), $language);

                    if (empty($title)) {
                        continue;
                    }

                    if (!isset($buildUrls[$languageId])) {
                        $buildUrls[$languageId] = [
                            'urls' => [],
                            'salesChannel' => $registrationSalesChannel,
                        ];
                    }

                    $buildUrls[$languageId]['urls'][] = [
                        'salesChannelId' => $registrationSalesChannel->getId(),
                        'foreignKey' => $group->getId(),
                        'routeName' => self::ROUTE_NAME,
                        'pathInfo' => '/customer-group-registration/' . $group->getId(),
                        'isCanonical' => true,
                        'seoPathInfo' => '/' . $this->slugify->slugify($title),
                    ];
                }
            }
        }

        foreach ($buildUrls as $languageId => $config) {
            $context = new Context(
                $context->getSource(),
                $context->getRuleIds(),
                $context->getCurrencyId(),
                [$languageId]
            );

            $this->persister->updateSeoUrls(
                $context,
                self::ROUTE_NAME,
                array_column($config['urls'], 'foreignKey'),
                $config['urls'],
                $config['salesChannel']
            );
        }
    }

    private function getTranslatedTitle(?CustomerGroupTranslationCollection $translations, LanguageEntity $language): string
    {
        if ($translations === null) {
            return '';
        }

        // Requested translation
        foreach ($translations as $translation) {
            if ($translation->getLanguageId() === $language->getId() && $translation->getRegistrationTitle() !== null) {
                return $translation->getRegistrationTitle();
            }
        }

        // Inherited translation
        foreach ($translations as $translation) {
            if ($translation->getLanguageId() === $language->getParentId() && $translation->getRegistrationTitle() !== null) {
                return $translation->getRegistrationTitle();
            }
        }

        // System Language
        foreach ($translations as $translation) {
            if ($translation->getLanguageId() === Defaults::LANGUAGE_SYSTEM && $translation->getRegistrationTitle() !== null) {
                return $translation->getRegistrationTitle();
            }
        }

        return '';
    }
}
