<?php declare(strict_types=1);

namespace SwagTestPluginAcl;

use Cicada\Core\Framework\Plugin;

class SwagTestPluginAclProductWriter extends Plugin
{
    public function enrichPrivileges(): array
    {
        return [
            'product.writer' => [
                'swag_demo_data:write',
            ],
        ];
    }
}
