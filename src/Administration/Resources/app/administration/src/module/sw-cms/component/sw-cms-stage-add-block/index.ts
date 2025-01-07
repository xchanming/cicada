import template from './sw-cms-stage-add-block.html.twig';
import './sw-cms-stage-add-block.scss';

/**
 * @private
 * @package discovery
 */
export default Cicada.Component.wrapComponentConfig({
    template,

    compatConfig: Cicada.compatConfig,

    emits: ['stage-block-add'],
});
