<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Api\Response;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Package('core')]
class JsonApiResponse extends JsonResponse
{
    protected function update(): static
    {
        parent::update();

        $this->headers->set('Content-Type', 'application/vnd.api+json');

        return $this;
    }
}
