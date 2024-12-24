/**
 * @package admin
 *
 * @private
 * @module core/factory/context
 * @param {Object} context
 * @type factory
 */
export default function createContext(context = {}) {
    const Defaults = Cicada.Defaults;
    const isDevMode = process.env.NODE_ENV !== 'production';
    const installationPath = getInstallationPath(context, isDevMode);
    const apiPath = `${installationPath}/api`;

    const languageId = localStorage.getItem('sw-admin-current-language') || Defaults.systemLanguageId;

    // set initial context
    Cicada.State.commit('context/setApiInstallationPath', installationPath);
    Cicada.State.commit('context/setApiApiPath', apiPath);
    Cicada.State.commit('context/setApiApiResourcePath', `${apiPath}`);
    Cicada.State.commit('context/setApiAssetsPath', getAssetsPath(context.assetPath, isDevMode));
    Cicada.State.commit('context/setApiLanguageId', languageId);
    Cicada.State.commit('context/setApiInheritance', false);

    if (isDevMode) {
        Cicada.State.commit('context/setApiSystemLanguageId', Defaults.systemLanguageId);
        Cicada.State.commit('context/setApiLiveVersionId', Defaults.versionId);
    }

    // assign unknown context information
    Object.entries(context).forEach(
        ([
            key,
            value,
        ]) => {
            Cicada.State.commit('context/addApiValue', { key, value });
        },
    );

    return Cicada.Context.api;
}

/**
 * Provides the installation path of the application. The path provides the scheme, host and sub directory.
 *
 * @param {Object} context
 * @param {Boolean} isDevMode
 * @returns {string}
 */
function getInstallationPath(context, isDevMode) {
    if (isDevMode) {
        return '';
    }

    let fullPath = '';
    if (context.schemeAndHttpHost?.length) {
        fullPath = `${context.schemeAndHttpHost}${context.basePath}`;
    }

    return fullPath;
}

/**
 * Provides the path to the assets directory.
 *
 * @param {String} installationPath
 * @param {Boolean} isDevMode
 * @returns {string}
 */
function getAssetsPath(installationPath, isDevMode) {
    if (isDevMode) {
        return '/bundles/';
    }

    return `${installationPath}/bundles/`;
}
