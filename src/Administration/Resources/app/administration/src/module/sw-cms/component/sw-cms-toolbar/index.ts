import template from './sw-cms-toolbar.html.twig';
import './sw-cms-toolbar.scss';

/**
 * @sw-package discovery
 */
// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default Cicada.Component.wrapComponentConfig({
    template,

    compatConfig: Cicada.compatConfig,
});
