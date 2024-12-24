<?php declare(strict_types=1);

namespace Cicada\Core\Installer\Controller;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Installer\Requirements\RequirementsValidatorInterface;
use Cicada\Core\Installer\Requirements\Struct\RequirementsCheckCollection;
use Cicada\Core\Maintenance\System\Service\JwtCertificateGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Package('core')]
class RequirementsController extends InstallerController
{
    private readonly string $jwtDir;

    /**
     * @param iterable|RequirementsValidatorInterface[] $validators
     */
    public function __construct(
        private readonly iterable $validators,
        private readonly JwtCertificateGenerator $jwtCertificateGenerator,
        string $projectDir
    ) {
        $this->jwtDir = $projectDir . '/config/jwt';
    }

    #[Route(path: '/installer/requirements', name: 'installer.requirements', methods: ['GET', 'POST'])]
    public function requirements(Request $request): Response
    {
        $checks = new RequirementsCheckCollection();

        foreach ($this->validators as $validator) {
            $checks = $validator->validateRequirements($checks);
        }

        if ($request->isMethod('POST') && !$checks->hasError()) {
            // The JWT dir exist and is writable, so we generate a new key pair
            $this->jwtCertificateGenerator->generate(
                $this->jwtDir . '/private.pem',
                $this->jwtDir . '/public.pem'
            );

            return $this->redirectToRoute('installer.license');
        }

        return $this->renderInstaller('@Installer/installer/requirements.html.twig', ['requirementChecks' => $checks]);
    }
}
