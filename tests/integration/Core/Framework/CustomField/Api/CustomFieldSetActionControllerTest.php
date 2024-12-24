<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\CustomField\Api;

use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('slow')]
class CustomFieldSetActionControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    public function testGetAvailableRelations(): void
    {
        $this->getBrowser()->request('GET', '/api/_action/attribute-set/relations');
        $response = $this->getBrowser()->getResponse();

        static::assertIsString($response->getContent());
        static::assertEquals(200, $response->getStatusCode());
        static::assertEquals('application/json', $response->headers->get('Content-Type'));

        $availableRelations = json_decode($response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertNotEmpty($availableRelations);

        static::assertContains('product', $availableRelations);
        static::assertNotContains('product_translation', $availableRelations);

        static::assertContains('product_manufacturer', $availableRelations);
        static::assertNotContains('product-manufacturer', $availableRelations);
    }
}
