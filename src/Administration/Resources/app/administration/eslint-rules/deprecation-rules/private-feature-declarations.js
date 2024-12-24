/**
 * @package admin
 */

/* eslint-disable max-len */

const nodeContainsLeadingBlockComment = (node) => {
    const leadingComment = node?.parent?.comments?.find(c => c.range[1] === (node.range[0] - 1));
    if (!leadingComment) {
        return false;
    }

    return leadingComment.type === 'Block' && (leadingComment.value.includes('@private') || leadingComment.value.includes('@deprecated tag:'));
};

/**
 * This rule validates that new features are either private or deprecated to be private in the future.
 *
 * Invalid:
 * class Example {}
 * Component.register('foo', {});
 * Cicada.Component.register('bar', {});
 * Module.register('foo', {});
 * Cicada.Module.register('bar', {});
 * export const foo = 'foo';
 * export default foo;
 *
 * Valid:
 * \**
 *  * @private
 *  *\
 * class Example {}
 *
 * \**
 *  * @deprecated tag:v6.X.0 - Will be @private
 *  *\
 * class Example {}
 *
 * \**
 *  * @private
 *  *\
 * Component.register('foo', {});
 *
 * \**
 *  * @deprecated tag:v6.X.0 - Will be @private
 *  *\
 * Cicada.Component.register('bar', {});
 *
 * \**
 *  * @private
 *  *\
 * Module.register('foo', {});
 *
 * \**
 *  * @deprecated tag:v6.X.0 - Will be @private
 *  *\
 * Cicada.Module.register('bar', {});
 *
 * \**
 *  * @private
 *  *\
 * export const foo = 'foo';
 *
 * \**
 *  * @deprecated tag:v6.X.0 - Will be @private
 *  *\
 * export default foo;
 */
/** @type {import('eslint').Rule.RuleModule} */
module.exports = {
    meta: {
        type: 'problem',

        docs: {
            description: 'New features have to be private',
            recommended: true,
            url: 'https://handbook.cicada.com/Product/Product/Components/Admin/NewFeatures',
        },
    },
    create(context) {
        return {
            ExportDefaultDeclaration(node) {
                if (nodeContainsLeadingBlockComment(node)) {
                    return;
                }

                context.report({
                    node,
                    message: 'New exports need to be private. Old exports should be @deprecated tag:v6.X.0 - Will be private',
                });
            },
            ExportNamedDeclaration(node) {
                if (nodeContainsLeadingBlockComment(node)) {
                    return;
                }

                context.report({
                    node,
                    message: 'New exports need to be private. Old exports should be @deprecated tag:v6.X.0 - Will be private',
                });
            },
            ExpressionStatement(node) {
                const root = node.expression?.callee?.object;
                const rootObject = root?.name;
                const property = node.expression?.callee?.property?.name;

                if (!property || property !== 'register') {
                    return;
                }

                let isFeatureRegister = false;

                if (rootObject === 'Component' && property === 'register') {
                    isFeatureRegister = true;
                }

                if (!isFeatureRegister && root?.object && root?.property && root?.object?.name === 'Cicada' && root.property.name === 'Component' && property === 'register') {
                    isFeatureRegister = true;
                }

                if (!isFeatureRegister && rootObject === 'Module' && property === 'register') {
                    isFeatureRegister = true;
                }

                if (!isFeatureRegister && root?.object && root?.property && root?.object?.name === 'Cicada' && root.property.name === 'Module' && property === 'register') {
                    isFeatureRegister = true;
                }

                if (!isFeatureRegister && rootObject === 'Service' && property === 'register') {
                    isFeatureRegister = true;
                }

                if (!isFeatureRegister && root?.callee?.object && root?.callee?.property && root?.callee?.object.name === 'Cicada' && root.callee.property.name === 'Service' && property === 'register') {
                    isFeatureRegister = true;
                }

                if (!isFeatureRegister || nodeContainsLeadingBlockComment(node)) {
                    return;
                }

                context.report({
                    node,
                    message: 'New features need to be private. Old features should be @deprecated tag:v6.X.0 - Will be private',
                });
            },
        };
    },
};
