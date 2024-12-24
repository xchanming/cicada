<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Twig;

use Cicada\Core\Framework\Log\Package;
use Twig\Environment;

#[Package('core')]
class TwigVariableParserFactory
{
    public function getParser(Environment $twig): TwigVariableParser
    {
        return new TwigVariableParser($twig);
    }
}
