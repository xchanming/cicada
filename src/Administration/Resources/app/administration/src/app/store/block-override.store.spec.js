/**
 * @sw-package framework
 */
import './block-override.store';

describe('block-override.store', () => {
    let store;

    beforeEach(() => {
        store = Cicada.Store.get('blockOverride');
    });

    it('has initial state', () => {
        expect(store.blockContext).toStrictEqual({});
    });
});
