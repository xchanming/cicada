<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Api\ApiDefinition\Generator\OpenApi\_fixtures;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Routing\Annotation\Entity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use OpenApi\Annotations as OA;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class StoreApiTestOtherRoute
{
    /**
     * @Entity("test")
     *
     * @OA\Post(
     *      path="/test",
     *      summary="A test route",
     *      operationId="readOtherInternalTest",
     *      tags={"Admin API", "Test"},
     *
     *      @OA\Parameter(name="Api-Basic-Parameters"),
     *
     *      @OA\Response(
     *          response="200",
     *          description="Success"
     *     )
     * )
     */
    #[Route(path: '/api/test', name: 'api.test', defaults: ['_loginRequired' => true], methods: ['GET'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): Response
    {
        return new Response();
    }
}
