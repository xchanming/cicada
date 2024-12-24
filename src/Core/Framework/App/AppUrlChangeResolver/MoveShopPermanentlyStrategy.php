<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\AppUrlChangeResolver;

use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use Cicada\Core\Framework\App\Lifecycle\AppLoader;
use Cicada\Core\Framework\App\Lifecycle\Registration\AppRegistrationService;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;

/**
 * @internal only for use by the app-system
 *
 * Resolver used when shop is moved from one URL to another
 * and the shopId (and the data in the app backends associated with it) should be kept
 *
 * Will run through the registration process for all apps again
 * with the new appUrl so the apps can save the new URL and generate new Secrets
 * that way communication from the old shop to the app backend will be blocked in the future
 */
#[Package('core')]
class MoveShopPermanentlyStrategy extends AbstractAppUrlChangeStrategy
{
    final public const STRATEGY_NAME = 'move-shop-permanently';

    public function __construct(
        AppLoader $appLoader,
        EntityRepository $appRepository,
        AppRegistrationService $registrationService,
        private readonly ShopIdProvider $shopIdProvider
    ) {
        parent::__construct($appLoader, $appRepository, $registrationService);
    }

    public function getDecorated(): AbstractAppUrlChangeStrategy
    {
        throw new DecorationPatternException(self::class);
    }

    public function getName(): string
    {
        return self::STRATEGY_NAME;
    }

    public function getDescription(): string
    {
        return 'Use this URL for communicating with installed apps, this will disable communication to apps on the old
        URLs installation, but the app-data from the old installation will be available in this installation.';
    }

    public function resolve(Context $context): void
    {
        try {
            $this->shopIdProvider->getShopId();

            // no resolution needed
            return;
        } catch (AppUrlChangeDetectedException $e) {
            $this->shopIdProvider->setShopId($e->getShopId(), (string) EnvironmentHelper::getVariable('APP_URL'));
        }

        $this->forEachInstalledApp($context, function (Manifest $manifest, AppEntity $app, Context $context): void {
            $this->reRegisterApp($manifest, $app, $context);
        });
    }
}
