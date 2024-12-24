/**
 * @package admin
 *
 * @private
 * @module core/factory/context
 * @param {Object} context
 * @type factory
 */
export default function createContext(context = {}) {
    // set initial context
    Cicada.State.commit('context/setAppEnvironment', process.env.NODE_ENV);
    Cicada.State.commit('context/setAppFallbackLocale', 'en-GB');

    // assign unknown context information
    Object.entries(context).forEach(
        ([
            key,
            value,
        ]) => {
            Cicada.State.commit('context/addAppValue', { key, value });
        },
    );

    return Cicada.Context.app;
}
