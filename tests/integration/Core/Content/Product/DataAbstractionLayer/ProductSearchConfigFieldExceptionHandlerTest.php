<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\DataAbstractionLayer;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Exception\DuplicateProductSearchConfigFieldException;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;

/**
 * @internal
 */
class ProductSearchConfigFieldExceptionHandlerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testDuplicateInsert(): void
    {
        static::getContainer()->get(Connection::class)
            ->executeStatement('DELETE FROM product_search_config');

        $ids = new IdsCollection();
        $config = [
            'id' => $ids->get('config'),
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'andLogic' => true,
            'minSearchLength' => 3,
            'configFields' => [
                ['id' => $ids->get('field-1'), 'field' => 'test'],
                ['id' => $ids->get('field-2'), 'field' => 'test'],
            ],
        ];

        static::expectException(DuplicateProductSearchConfigFieldException::class);
        static::expectExceptionMessage('Product search config with field test already exists.');

        static::getContainer()->get('product_search_config.repository')
            ->create([$config], Context::createDefaultContext());
    }
}
