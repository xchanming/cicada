---
title: Rework Document Generator
issue: NEXT-17708
---
# Core
* Added new composer's dependencies `tecnickcom/tcpdf:6.4.4` and `setasign/fpdi:2.3.6` to merge generated documents
* Added a new service `Cicada\Core\Checkout\Document\Service\DocumentGenerator`
* Added new renderer classes in
    * `\Cicada\Core\Checkout\Document\Renderer\AbstractDocumentRenderer`
    * `\Cicada\Core\Checkout\Document\Renderer\InvoiceRenderer`
    * `\Cicada\Core\Checkout\Document\Renderer\StornoRenderer`
    * `\Cicada\Core\Checkout\Document\Renderer\CreditNoteRenderer`
    * `\Cicada\Core\Checkout\Document\Renderer\DeliveryNoteRenderer`
* Deprecated `Cicada\Core\Checkout\Document\DocumentService`, use `Cicada\Core\Checkout\Document\Service\DocumentGenerator` instead
* Deprecated `\Cicada\Core\Checkout\Document\DocumentGeneratorController::createDocument`, use `createDocuments` instead
* Deprecated`\Cicada\Core\Checkout\Document\DocumentGenerator\DocumentGeneratorInterface` and its implementations use the `AbstractDocumentRenderer` instead
* Deprecated`\Cicada\Core\Checkout\Document\DocumentGenerator\DocumentGeneratorRegistry` use the `DocumentRendererRegistry` instead
* Added a new service `\Cicada\Core\Checkout\Document\Service\DocumentConfigLoader` to load document's config
* Added a new service `\Cicada\Core\Checkout\Document\Service\DocumentMerger` to combine generated document into one pdf file
* Added a new Struct class for Order Document in `Cicada\Core\Checkout\Document\Struct\DocumentGenerateOperation`
* Added a new route `Cicada\Core\Checkout\Document\SalesChannel\DocumentRoute` to provide store-api route for downloading generated document's content
* Deprecated`\Cicada\Core\Checkout\Document\Event\DocumentOrderCriteriaEvent`
* Added these new events after fetching orders in renderer classes:
    * `Cicada\Core\Checkout\Document\Event\DocumentOrderEvent`   
    * `Cicada\Core\Checkout\Document\Event\InvoiceOrdersEvent`   
    * `Cicada\Core\Checkout\Document\Event\CreditNoteOrdersEvent`   
    * `Cicada\Core\Checkout\Document\Event\DeliveryNoteOrdersEvent`   
    * `Cicada\Core\Checkout\Document\Event\StornoOrdersEvent`
* Added a new service `Cicada\Core\Checkout\Document\Service\ReferenceInvoiceLoader` to load the reference invoice of a given order
* Added new method `downloadBulkDocuments` in `Core/Checkout/Document/Controller/DocumentController.php` to allow downloading multiple documents
___
# API
* Added new `POST` endpoint `/api/_action/order/document/{documentType}/create` in `\Cicada\Core\Checkout\Document\DocumentGeneratorController`. This endpoint is used for generating order documents in bulk
* Deprecated `POST` endpoint `/api/_action/order/{orderId}/document/{documentTypeName}` in `\Cicada\Core\Checkout\Document\DocumentGeneratorController`. Use `/api/_action/order/document/{documentType}/create` instead
* Added new `POST` endpoint `/api/_action/order/document/download` in `\Cicada\Core\Checkout\Document\DocumentGeneratorController`. This endpoint is used for merging order documents and downloading them in one pdf file
* Added new store-api route `/store-api/document/download/{documentId}/{deepLinkCode}` to download generated document
___
# Administration
* Changed method `createDocument` in `src/core/service/api/document.api.service.js` to use the new create documents endpoint
___
# Storefront
* Deprecated `Cicada\Storefront\Page\Account\Document\DocumentPageLoader` and `Cicada\Storefront\Page\Account\Document\DocumentPage` and `Cicada\Storefront\Page\Account\Document\DocumentPageLoadedEvent` due to unused
* Changed `Cicada\Storefront\Controller\DocumentController` to use `Cicada\Core\Checkout\Document\SalesChannel\DocumentRoute` to load document blob file
___
# Upgrade Information

## Deprecate old document generation endpoint, introduce new bulk order's documents generator endpoint

* Endpoint and payload:
```
POST /api/_action/order/document/{documentType}/create
[
    {
        "fileType": "pdf",
        "orderId": "012cd563cf8e4f0384eed93b5201cc98",
        "static": true,
        "config": {
            "documentComment": "Some comment",
            "documentNumber": "1002",
            "documentDate": "2021-12-13T00:00:00.000Z"
        }
    }, 
    {        
        "fileType": "pdf",
        "orderId": "012cd563cf8e4f0384eed93b5201cc99",
        "static": true,
        "config": {
            "documentComment": "Another comment",
            "documentNumber": "1003",
            "documentDate": "2021-12-13T00:00:00.000Z"
        }
    }
]
```

## New bulk order's documents downloading endpoint

This endpoint is used for merging multiple documents at one pdf file and download the merged pdf file

* Endpoint and payload:
```
POST /api/_action/order/document/download
{
    "documentIds": [
        "012cd563cf8e4f0384eed93b5201cc98",
        "075fb241b769444bb72431f797fd5776",
    ],
}
```

## New Store-Api route to download document

* Use `/store-api/document/download/{documentId}/{deepLinkCode}` route to download generated document of the given id

## Deprecation of DocumentPageLoader

* The `\Cicada\Storefront\Page\Account\Document\DocumentPageLoader` and its page, page loaded event was deprecated and will be removed in v6.5.0.0 due to unused, please use the newly added `\Cicada\Core\Checkout\Document\SalesChannel\DocumentRoute` instead to download generated document. 

## Deprecation of Document generators, introduce Document renderer services

* All the document generators in `Cicada\Core\Checkout\Document\DocumentGenerator` (tagged as `document.generator`) will be deprecated and will be removed in v6.5.0.0, please adjust your changes if you're touching these services, you might want to decorate `Cicada\Core\Checkout\Document\Renderer\*` (tagged as `document.renderer`) instead
* If you need to manipulate the fetched orders in renderer services, you can listen to according events which extends from `Cicada\Core\Checkout\Document\Event\DocumentOrderEvent`
