<?php declare(strict_types=1);

namespace Cicada\Core\Installer\Controller;

use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Package('core')]
class SelectLanguagesController extends InstallerController
{
    public function __construct()
    {
    }

    #[Route(path: '/installer', name: 'installer.language-selection', methods: ['GET'])]
    public function languageSelection(): Response
    {
        return $this->renderInstaller('@Installer/installer/language-selection.html.twig');
    }
}
