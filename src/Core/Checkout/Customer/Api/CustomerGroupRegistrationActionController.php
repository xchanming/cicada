<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Api;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Checkout\Customer\Event\CustomerGroupRegistrationAccepted;
use Cicada\Core\Checkout\Customer\Event\CustomerGroupRegistrationDeclined;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextRestorer;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['api']])]
#[Package('checkout')]
class CustomerGroupRegistrationActionController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $customerRepository,
        private readonly EntityRepository $customerGroupRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SalesChannelContextRestorer $restorer
    ) {
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/api/_action/customer-group-registration/accept', name: 'api.customer-group.accept', methods: ['POST'], requirements: ['version' => '\d+'])]
    public function accept(Request $request, Context $context): JsonResponse
    {
        $customerIds = $this->getRequestCustomerIds($request);

        $silentError = $request->request->getBoolean('silentError');

        $customers = $this->fetchCustomers($customerIds, $context, $silentError);

        $updateData = [];

        foreach ($customers as $customer) {
            $updateData[] = [
                'id' => $customer->getId(),
                'requestedGroupId' => null,
                'groupId' => $customer->getRequestedGroupId(),
            ];
        }

        $this->customerRepository->update($updateData, $context);

        foreach ($customers as $customer) {
            $salesChannelContext = $this->restorer->restoreByCustomer($customer->getId(), $context);

            /** @var CustomerEntity $customer */
            $customer = $salesChannelContext->getCustomer();

            $criteria = new Criteria([$customer->getGroupId()]);
            $criteria->setLimit(1);
            $customerRequestedGroup = $this->customerGroupRepository->search($criteria, $salesChannelContext->getContext())->first();

            if ($customerRequestedGroup === null) {
                throw CustomerException::customerGroupNotFound($customer->getGroupId());
            }

            $this->eventDispatcher->dispatch(new CustomerGroupRegistrationAccepted(
                $customer,
                $customerRequestedGroup,
                $salesChannelContext->getContext()
            ));
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @throws Exception
     */
    #[Route(path: '/api/_action/customer-group-registration/decline', name: 'api.customer-group.decline', methods: ['POST'], requirements: ['version' => '\d+'])]
    public function decline(Request $request, Context $context): JsonResponse
    {
        $customerIds = $this->getRequestCustomerIds($request);

        $silentError = $request->request->getBoolean('silentError');

        $customers = $this->fetchCustomers($customerIds, $context, $silentError);

        $updateData = [];

        foreach ($customers as $customer) {
            $updateData[] = [
                'id' => $customer->getId(),
                'requestedGroupId' => null,
            ];
        }

        $this->customerRepository->update($updateData, $context);

        foreach ($customers as $customer) {
            $salesChannelContext = $this->restorer->restoreByCustomer($customer->getId(), $context);

            /** @var CustomerEntity $customer */
            $customer = $salesChannelContext->getCustomer();

            $criteria = new Criteria([$customer->getGroupId()]);
            $criteria->setLimit(1);
            $customerRequestedGroup = $this->customerGroupRepository->search($criteria, $salesChannelContext->getContext())->first();

            if ($customerRequestedGroup === null) {
                throw CustomerException::customerGroupNotFound($customer->getGroupId());
            }

            $this->eventDispatcher->dispatch(new CustomerGroupRegistrationDeclined(
                $customer,
                $customerRequestedGroup,
                $salesChannelContext->getContext()
            ));
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @return array<string>
     */
    private function getRequestCustomerIds(Request $request): array
    {
        $customerIds = $request->request->all('customerIds');

        if (!empty($customerIds)) {
            $customerIds = array_unique($customerIds);
        }

        if (empty($customerIds)) {
            throw CustomerException::customerIdsParameterIsMissing();
        }

        return $customerIds;
    }

    /**
     * @param array<string> $customerIds
     *
     * @return array<CustomerEntity>
     */
    private function fetchCustomers(array $customerIds, Context $context, bool $silentError = false): array
    {
        $criteria = new Criteria($customerIds);
        $result = $this->customerRepository->search($criteria, $context);

        $customers = [];

        if ($result->getTotal()) {
            /** @var CustomerEntity $customer */
            foreach ($result->getElements() as $customer) {
                if ($customer->getRequestedGroupId() === null) {
                    if ($silentError === false) {
                        throw CustomerException::groupRequestNotFound($customer->getId());
                    }

                    continue;
                }

                $customers[] = $customer;
            }

            return $customers;
        }

        throw CustomerException::customersNotFound($customerIds);
    }
}
