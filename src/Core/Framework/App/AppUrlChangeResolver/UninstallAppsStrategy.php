<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\AppUrlChangeResolver;

use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\Event\AppDeactivatedEvent;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Storefront\Theme\ThemeAppLifecycleHandler;

/**
 * @internal only for use by the app-system
 *
 * Resolver used when apps should be uninstalled
 * and the shopId should be regenerated, meaning the old shops and old apps work like before
 * apps in the current installation will be uninstalled without informing them about that (as they still run on the old installation)
 */
#[Package('core')]
class UninstallAppsStrategy extends AbstractAppUrlChangeStrategy
{
    final public const STRATEGY_NAME = 'uninstall-apps';

    /**
     * @param EntityRepository<AppCollection> $appRepository
     */
    public function __construct(
        private readonly EntityRepository $appRepository,
        private readonly ShopIdProvider $shopIdProvider,
        private readonly ?ThemeAppLifecycleHandler $themeLifecycleHandler
    ) {
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
        return 'Uninstall all apps on this URL, so app communication on the old URLs installation keeps
        working like before.';
    }

    public function resolve(Context $context): void
    {
        $this->shopIdProvider->deleteShopId();

        foreach ($this->appRepository->search(new Criteria(), $context)->getEntities() as $app) {
            // Delete app manually, to not inform the app backend about the deactivation
            // as the app is still running in the old shop with the same shopId
            if ($this->themeLifecycleHandler) {
                $this->themeLifecycleHandler->handleUninstall(new AppDeactivatedEvent($app, $context));
            }
            $this->appRepository->delete([['id' => $app->getId()]], $context);
        }
    }
}
