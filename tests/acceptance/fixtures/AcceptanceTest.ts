import { test as CicadaTestSuite, mergeTests } from '@cicada-ag/acceptance-test-suite';
import { test as shopCustomerTasks } from '@tasks/ShopCustomerTasks';
import { test as shopAdminTasks } from '@tasks/ShopAdminTasks';

export * from '@cicada-ag/acceptance-test-suite';

export const test = mergeTests(
    CicadaTestSuite,
    shopCustomerTasks,
    shopAdminTasks,
);
