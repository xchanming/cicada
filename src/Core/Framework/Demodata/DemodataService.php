<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Demodata;

use Cicada\Core\Framework\Adapter\Console\CicadaStyle;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\Demodata\Faker\Commerce;
use Cicada\Core\Framework\Log\Package;
use Faker\Factory;
use Faker\Generator;
use Maltyxx\ImagesGenerator\ImagesGeneratorProvider;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @final
 */
#[Package('core')]
class DemodataService
{
    /**
     * @internal
     *
     * @param \IteratorAggregate<DemodataGeneratorInterface> $generators
     */
    public function __construct(
        private readonly \IteratorAggregate $generators,
        private readonly string $projectDir,
        private readonly DefinitionInstanceRegistry $registry
    ) {
    }

    public function generate(DemodataRequest $request, Context $context, ?SymfonyStyle $console): DemodataContext
    {
        if (!$console) {
            $console = new CicadaStyle(new ArgvInput(), new NullOutput());
        }

        $faker = $this->getFaker();

        $demodataContext = new DemodataContext($context, $faker, $this->projectDir, $console, $this->registry);

        foreach ($request->all() as $definitionClass => $numberOfItems) {
            if ($numberOfItems === 0) {
                continue;
            }

            $definition = $this->registry->get($definitionClass);

            $console->section(\sprintf('Generating %d items for %s', $numberOfItems, $definition->getEntityName()));

            $validGenerators = array_filter(iterator_to_array($this->generators), static fn (DemodataGeneratorInterface $generator) => $generator->getDefinition() === $definitionClass);

            if (empty($validGenerators)) {
                throw new \RuntimeException(
                    \sprintf('Could not generate demodata for "%s" because no generator is registered.', $definitionClass)
                );
            }

            $start = microtime(true);

            foreach ($validGenerators as $generator) {
                $generator->generate($numberOfItems, $demodataContext, $request->getOptions($definitionClass));
            }

            $end = microtime(true) - $start;

            $console->note(\sprintf('Took %f seconds', $end));

            $demodataContext->setTiming($definition, $numberOfItems, $end);
        }

        return $demodataContext;
    }

    private function getFaker(): Generator
    {
        $faker = Factory::create('de-DE');
        $faker->addProvider(new Commerce($faker));
        $faker->addProvider(new ImagesGeneratorProvider($faker));

        return $faker;
    }
}
