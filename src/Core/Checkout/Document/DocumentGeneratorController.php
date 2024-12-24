<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document;

use Cicada\Core\Checkout\Document\FileGenerator\FileTypes;
use Cicada\Core\Checkout\Document\Service\DocumentGenerator;
use Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Validation\Constraint\Uuid;
use Cicada\Core\Framework\Validation\DataValidationDefinition;
use Cicada\Core\Framework\Validation\DataValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('checkout')]
class DocumentGeneratorController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly DocumentGenerator $documentGenerator,
        private readonly DecoderInterface $serializer,
        private readonly DataValidator $dataValidator
    ) {
    }

    #[Route(path: '/api/_action/order/document/{documentTypeName}/create', name: 'api.action.document.bulk.create', methods: ['POST'], defaults: ['_acl' => ['document:create']])]
    public function createDocuments(Request $request, string $documentTypeName, Context $context): JsonResponse
    {
        $documents = $this->serializer->decode($request->getContent(), 'json');

        if (empty($documents) || !\is_array($documents)) {
            throw RoutingException::invalidRequestParameter('Request parameters must be an array of documents object');
        }

        $operations = [];

        $definition = new DataValidationDefinition();
        $definition->addList(
            'documents',
            (new DataValidationDefinition())
                ->add('orderId', new NotBlank())
                ->add('fileType', new Choice([FileTypes::PDF]))
                ->add('config', new Type('array'))
                ->add('static', new Type('bool'))
                ->add('referencedDocumentId', new Uuid())
        );

        $this->dataValidator->validate($documents, $definition);

        foreach ($documents as $operation) {
            $operations[$operation['orderId']] = new DocumentGenerateOperation(
                $operation['orderId'],
                $operation['fileType'] ?? FileTypes::PDF,
                $operation['config'] ?? [],
                $operation['referencedDocumentId'] ?? null,
                $operation['static'] ?? false
            );
        }

        return new JsonResponse($this->documentGenerator->generate($documentTypeName, $operations, $context));
    }

    #[Route(path: '/api/_action/document/{documentId}/upload', name: 'api.action.document.upload', methods: ['POST'], defaults: ['_acl' => ['document:update']])]
    public function uploadToDocument(Request $request, string $documentId, Context $context): JsonResponse
    {
        $documentIdStruct = $this->documentGenerator->upload(
            $documentId,
            $context,
            $request
        );

        return new JsonResponse(
            [
                'documentId' => $documentIdStruct->getId(),
                'documentMediaId' => $documentIdStruct->getMediaId(),
                'documentDeepLink' => $documentIdStruct->getDeepLinkCode(),
            ]
        );
    }
}
