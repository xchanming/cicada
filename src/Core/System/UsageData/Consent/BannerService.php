<?php declare(strict_types=1);

namespace Cicada\Core\System\UsageData\Consent;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\User\Aggregate\UserConfig\UserConfigEntity;

/**
 * @internal
 */
#[Package('data-services')]
class BannerService
{
    public const USER_CONFIG_KEY_HIDE_CONSENT_BANNER = 'core.usageData.hideConsentBanner';

    public function __construct(private readonly EntityRepository $userConfigRepository)
    {
    }

    public function hideConsentBannerForUser(string $userId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('userId', $userId));
        $criteria->addFilter(new EqualsFilter('key', self::USER_CONFIG_KEY_HIDE_CONSENT_BANNER));

        $userConfigId = $this->userConfigRepository->searchIds($criteria, $context)->firstId();

        $this->userConfigRepository->upsert([
            [
                'id' => $userConfigId ?: Uuid::randomHex(),
                'userId' => $userId,
                'key' => self::USER_CONFIG_KEY_HIDE_CONSENT_BANNER,
                'value' => ['_value' => true],
            ],
        ], $context);
    }

    public function hasUserHiddenConsentBanner(string $userId, Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('key', self::USER_CONFIG_KEY_HIDE_CONSENT_BANNER));
        $criteria->addFilter(new EqualsFilter('userId', $userId));

        /** @var UserConfigEntity|null $userConfig */
        $userConfig = $this->userConfigRepository->search($criteria, $context)->first();
        if ($userConfig === null) {
            return false;
        }

        return $userConfig->getValue()['_value'] ?? false;
    }

    public function resetIsBannerHiddenForAllUsers(): void
    {
        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('key', self::USER_CONFIG_KEY_HIDE_CONSENT_BANNER));

        $userConfigs = $this->userConfigRepository->search($criteria, $context);
        if ($userConfigs->getTotal() === 0) {
            return;
        }

        $updates = [];

        /** @var UserConfigEntity $userConfig */
        foreach ($userConfigs->getElements() as $userConfig) {
            $updates[] = [
                'id' => $userConfig->getId(),
                'userId' => $userConfig->getUserId(),
                'key' => self::USER_CONFIG_KEY_HIDE_CONSENT_BANNER,
                'value' => ['_value' => false],
            ];
        }

        $this->userConfigRepository->upsert($updates, $context);
    }
}
