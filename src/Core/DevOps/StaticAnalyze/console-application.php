<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan;

use Cicada\Core\DevOps\StaticAnalyze\StaticAnalyzeKernel;
use Cicada\Core\Framework\Adapter\Kernel\KernelFactory;
use Cicada\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Symfony\Bundle\FrameworkBundle\Console\Application;

$classLoader = require __DIR__ . '/phpstan-bootstrap.php';

$pluginLoader = new StaticKernelPluginLoader($classLoader);

KernelFactory::$kernelClass = StaticAnalyzeKernel::class;

/** @var StaticAnalyzeKernel $kernel */
$kernel = KernelFactory::create(
    environment: 'phpstan_dev',
    debug: true,
    classLoader: $classLoader,
    pluginLoader: $pluginLoader
);

$kernel->boot();

return new Application($kernel);
