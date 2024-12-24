<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Webhook;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
class WebhookApiTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    public function testWriteWebhookViaApi(): void
    {
        $this->getBrowser()->request(
            'POST',
            '/api/webhook/',
            [
                'name' => 'My super webhook',
                'eventName' => 'product.written',
                'url' => 'http://localhost',
            ]
        );

        $response = $this->getBrowser()->getResponse();

        static::assertEquals(204, $response->getStatusCode(), \print_r($response->getContent(), true));
    }
}
