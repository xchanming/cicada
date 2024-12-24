<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Script\Api;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\System\SalesChannel\Api\ResponseFields;
use Cicada\Core\System\SalesChannel\Api\StructEncoder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class ScriptResponseEncoder
{
    /**
     * @internal
     */
    public function __construct(private readonly StructEncoder $structEncoder)
    {
    }

    public function encodeToSymfonyResponse(ScriptResponse $scriptResponse, ResponseFields $responseFields, string $apiAlias): Response
    {
        $wrappedResponse = $scriptResponse->getInner();
        if ($wrappedResponse !== null) {
            return $wrappedResponse;
        }

        $data = $this->structEncoder->encode(new ArrayStruct($scriptResponse->getBody()->all(), $apiAlias), $responseFields);

        return new JsonResponse($data, $scriptResponse->getCode(), $scriptResponse->getHeaders());
    }
}
