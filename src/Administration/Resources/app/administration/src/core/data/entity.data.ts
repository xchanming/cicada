/**
 * @package admin
 */

import type Vue from 'vue';
import Entity, { assignSetterMethod } from '@cicada-ag/meteor-admin-sdk/es/_internals/data/Entity';

assignSetterMethod((draft, property, value) => {
    // @ts-expect-error
    Cicada.Application.view.setReactive(draft as Vue, property, value);
});

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default Entity;
