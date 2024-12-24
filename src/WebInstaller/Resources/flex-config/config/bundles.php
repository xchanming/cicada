<?php
declare(strict_types=1);

$bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true],
    Cicada\Core\Framework\Framework::class => ['all' => true],
    Cicada\Core\System\System::class => ['all' => true],
    Cicada\Core\Content\Content::class => ['all' => true],
    Cicada\Core\Checkout\Checkout::class => ['all' => true],
    Cicada\Core\Maintenance\Maintenance::class => ['all' => true],
    Cicada\Core\DevOps\DevOps::class => ['e2e' => true],
    Cicada\Core\Profiling\Profiling::class => ['all' => true],
    Cicada\Administration\Administration::class => ['all' => true],
    Cicada\Elasticsearch\Elasticsearch::class => ['all' => true],
    Cicada\Storefront\Storefront::class => ['all' => true],
];

if (class_exists(Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class)) {
    $bundles[Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class] = ['all' => true];
}

if (class_exists(Enqueue\Bundle\EnqueueBundle::class)) {
    $bundles[Enqueue\Bundle\EnqueueBundle::class] = ['all' => true];
}

if (class_exists(Enqueue\MessengerAdapter\Bundle\EnqueueAdapterBundle::class)) {
    $bundles[Enqueue\MessengerAdapter\Bundle\EnqueueAdapterBundle::class] = ['all' => true];
}

return $bundles;
