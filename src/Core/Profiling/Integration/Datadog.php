<?php declare(strict_types=1);

namespace Cicada\Core\Profiling\Integration;

use Cicada\Core\Framework\Log\Package;
use DDTrace\Contracts\Tracer;
use DDTrace\GlobalTracer;

/**
 * @internal experimental atm
 */
#[Package('core')]
class Datadog implements ProfilerInterface
{
    private array $spans = [];

    public function start(string $title, string $category, array $tags): void
    {
        if (!class_exists(GlobalTracer::class)) {
            return;
        }

        if (!interface_exists(Tracer::class)) {
            return;
        }

        if ($category !== 'cicada') {
            $category = 'cicada.' . $category;
        }

        /** @see \DDTrace\Tag::SERVICE_NAME */
        $tags = array_merge(['service.name' => $category], $tags);
        /** @var Tracer */
        $tracer = GlobalTracer::get();
        $span = $tracer->startActiveSpan($title, [
            'tags' => $tags,
        ]);

        $this->spans[$title] = $span;
    }

    public function stop(string $title): void
    {
        if (!class_exists(GlobalTracer::class)) {
            return;
        }

        $span = $this->spans[$title] ?? null;

        if ($span) {
            $span->close();
            unset($this->spans[$title]);
        }
    }
}
