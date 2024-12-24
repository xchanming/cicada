import type FilterFactoryData from 'src/core/data/filter-factory.data';

/**
 * @package admin
 */

const FilterFactory = Cicada.Classes._private.FilterFactory;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default function initializeFilterFactory() {
    const filterFactory = new FilterFactory();

    Cicada.Application.addServiceProvider('filterFactory', () => {
        return filterFactory as unknown as FilterFactoryData;
    });
}
