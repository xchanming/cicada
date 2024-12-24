<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Administration\Controller;

use Cicada\Administration\Controller\AdminTagController;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Tag\Service\FilterTagIdsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('administration')]
#[CoversClass(AdminTagController::class)]
class AdminTagControllerTest extends TestCase
{
    public function testFilterIds(): void
    {
        $filterTagIdsService = $this->createMock(FilterTagIdsService::class);
        $controller = new AdminTagController($filterTagIdsService);

        $response = $controller->filterIds(new Request(), new Criteria(), Context::createDefaultContext());
        static::assertNotFalse($response->getContent());
        static::assertJsonStringEqualsJsonString('{"total":0,"ids":[]}', $response->getContent());
    }
}
