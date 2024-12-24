<?php declare(strict_types=1);

use Composer\InstalledVersions;

$bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Cicada\Core\Profiling\Profiling::class => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true, 'test' => true],
    Cicada\Core\Framework\Framework::class => ['all' => true],
    Cicada\Core\System\System::class => ['all' => true],
    Cicada\Core\Content\Content::class => ['all' => true],
    Cicada\Core\Checkout\Checkout::class => ['all' => true],
    Cicada\Core\DevOps\DevOps::class => ['all' => true],
    Cicada\Core\Maintenance\Maintenance::class => ['all' => true],
    Cicada\Administration\Administration::class => ['all' => true],
    Cicada\Storefront\Storefront::class => ['all' => true],
    Cicada\Elasticsearch\Elasticsearch::class => ['all' => true],
    Cicada\Core\Service\Service::class => ['all' => true],
];

if (InstalledVersions::isInstalled('symfony/web-profiler-bundle')) {
    $bundles[Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class] = ['dev' => true, 'test' => true, 'phpstan_dev' => true];
}

return $bundles;
