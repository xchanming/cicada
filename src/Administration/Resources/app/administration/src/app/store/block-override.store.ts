/**
 * @sw-package framework
 * @private
 */
import useBlockContext from '../composables/use-block-context';

/**
 * @private
 */
const blockOverrideStore = Cicada.Store.register('blockOverride', useBlockContext);

/**
 * @private
 */
export default blockOverrideStore;

/**
 * @private
 */
export type BlockOverrideStore = ReturnType<typeof blockOverrideStore>;
