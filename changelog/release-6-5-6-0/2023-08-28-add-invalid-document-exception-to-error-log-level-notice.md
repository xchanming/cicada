---
title: Add invalid document exception to error log level notice
issue: NEXT-30170
---
# Core
* Added these following `error-codes` into the error log level `notice` of `cicada.yaml`:
  * `DOCUMENT__INVALID_DOCUMENT_ID`
  * `DOCUMENT__INVALID_GENERATOR_TYPE`
  * `DOCUMENT__ORDER_NOT_FOUND`
* Added these following exception classes into the `exception` part of `framework.yaml`:
  * `Cicada\Core\Checkout\Document\Exception\InvalidDocumentGeneratorTypeException`
  * `Cicada\Core\Checkout\Document\Exception\InvalidDocumentException`
  * `Cicada\Core\Checkout\Document\Exception\DocumentGenerationException`
  * `Cicada\Core\Checkout\Document\Exception\DocumentNumberAlreadyExistsException`
  * `Cicada\Core\Checkout\Document\Exception\InvalidDocumentRendererException`
  * `Cicada\Core\Checkout\Document\Exception\InvalidFileGeneratorTypeException`
