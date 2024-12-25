<?php declare(strict_types=1);

namespace Cicada\Core\Installer\Configuration;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Installer\Controller\ShopConfigurationController;
use Cicada\Core\Maintenance\User\Service\UserProvisioner;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @phpstan-import-type AdminUser from ShopConfigurationController
 */
#[Package('core')]
class AdminConfigurationService
{
    /**
     * @param AdminUser $user
     */
    public function createAdmin(array $user, Connection $connection): void
    {
        $userProvisioner = new UserProvisioner($connection);
        $userProvisioner->provision(
            $user['username'],
            $user['password'],
            [
                'name' => $user['name'],
                'email' => $user['email'],
            ]
        );
    }
}
