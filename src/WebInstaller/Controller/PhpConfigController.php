<?php
declare(strict_types=1);

namespace Cicada\WebInstaller\Controller;

use Cicada\Core\Framework\Log\Package;
use Cicada\WebInstaller\Services\PhpBinaryFinder;
use Cicada\WebInstaller\Services\RecoveryManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Package('core')]
class PhpConfigController extends AbstractController
{
    public function __construct(
        private readonly PhpBinaryFinder $binaryFinder,
        private readonly RecoveryManager $recoveryManager
    ) {
    }

    #[Route('/configure', name: 'configure', defaults: ['step' => 1])]
    public function index(Request $request): Response
    {
        try {
            $cicadaLocation = $this->recoveryManager->getCicadaLocation();
        } catch (\RuntimeException $e) {
            $cicadaLocation = null;
        }

        if ($phpBinary = $request->request->get('phpBinary')) {
            // Reset the latest version to force a new check
            $request->getSession()->remove('latestVersion');

            $request->getSession()->set('phpBinary', $phpBinary);

            return $this->redirectToRoute($cicadaLocation === null ? 'install' : 'update');
        }

        return $this->render('php_config.html.twig', [
            'phpBinary' => $request->getSession()->get('phpBinary', $this->binaryFinder->find()),
            'cicadaLocation' => $cicadaLocation,
        ]);
    }
}
