<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1585490020ActivateHoneypotCaptcha extends MigrationStep
{
    private const CONFIG_KEY = 'core.basicInformation.activeCaptchas';
    private const CAPTCHA_NAME = 'honeypot';

    public function getCreationTimestamp(): int
    {
        return 1585490020;
    }

    public function update(Connection $connection): void
    {
        $configPresent = $connection->fetchOne('SELECT 1 FROM `system_config` WHERE `configuration_key` = ?', [self::CONFIG_KEY]);

        if ($configPresent !== false) {
            // Captchas are already configured, don't alter the setting
            return;
        }

        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => self::CONFIG_KEY,
            'configuration_value' => \sprintf('{"_value": ["%s"]}', self::CAPTCHA_NAME),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
