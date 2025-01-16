/**
 * @sw-package after-sales
 */

import './index';
import FlowBuilderService from './flow-builder.service';

const { Service } = Cicada;

describe('src/module/sw-flow/service/index.ts', () => {
    it('should register flowBuilderService', () => {
        const flowBuilderService = Service('flowBuilderService');

        expect(flowBuilderService).toBeDefined();
        expect(flowBuilderService).toBeInstanceOf(FlowBuilderService);
    });
});
