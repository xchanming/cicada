/**
 * @sw-package framework
 */
import ErrorResolver from 'src/core/data/error-resolver.data';
import EntityFactory from 'src/core/data/entity-factory.data';

const entityFactory = new EntityFactory();

describe('src/core/data/error-resolver.data', () => {
    let errorResolver;

    beforeEach(() => {
        Object.defineProperty(Cicada.State, 'dispatch', {
            value: jest.fn(),
        });

        errorResolver = new ErrorResolver();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('resetApiErrors', () => {
        it('should dispatches "error/resetApiErrors" action', () => {
            errorResolver.resetApiErrors();

            expect(Cicada.State.dispatch).toHaveBeenCalledWith('error/resetApiErrors');
        });
    });

    describe('handleWriteErrors', () => {
        it('should throws an error if no errors are provided', () => {
            expect(() => {
                errorResolver.handleWriteErrors({});
            }).toThrow('[error-resolver] handleWriteError was called without errors');
        });

        it('should handles write errors and adds system errors', () => {
            const errors = [
                { source: { pointer: '/0/name' }, code: 'CODE1' },
                {
                    source: { pointer: '/0/translations/123123' },
                    code: 'CODE1',
                },
                {
                    source: { pointer: '' },
                    message: 'System Error',
                    code: 'CODE3',
                },
            ];

            const changeset = [
                {
                    entity: entityFactory.create('customer'),
                    changes: [
                        {
                            name: 'a',
                        },
                    ],
                },
                {
                    entity: entityFactory.create('customer'),
                    changes: [
                        {
                            name: 'c',
                        },
                    ],
                },
            ];

            errorResolver.handleWriteErrors(changeset, { errors });

            expect(Cicada.State.dispatch).toHaveBeenCalledTimes(2);
            expect(Cicada.State.dispatch).toHaveBeenNthCalledWith(1, 'error/addApiError', {
                expression: expect.anything(),
                error: expect.any(Cicada.Classes.CicadaError),
            });
            expect(Cicada.State.dispatch).toHaveBeenNthCalledWith(
                2,
                'error/addSystemError',
                expect.any(Cicada.Classes.CicadaError),
            );
        });

        it('should convert to CicadaError', () => {
            const errors = [
                { source: { pointer: '/0/name' }, code: 'CODE1' },
            ];

            const changeset = [
                {
                    entity: entityFactory.create('customer'),
                    changes: [
                        {
                            name: 'a',
                        },
                    ],
                },
            ];

            errorResolver.reduceErrorsByWriteIndex = jest.fn().mockReturnValue({
                system: [],
                0: {
                    name: {
                        code: 'CODE1',
                    },
                },
            });

            errorResolver.handleWriteErrors(changeset, { errors });

            expect(errorResolver.reduceErrorsByWriteIndex).toHaveBeenCalledTimes(1);
            expect(Cicada.State.dispatch).toHaveBeenNthCalledWith(1, 'error/addApiError', {
                expression: expect.anything(),
                error: expect.any(Cicada.Classes.CicadaError),
            });
        });
    });

    describe('getErrorPath', () => {
        it('should returns the correct error path', () => {
            const entity = {
                getEntityName: jest.fn(() => 'product'),
                id: 'abc123',
            };
            const currentField = 'name';

            const result = errorResolver.getErrorPath(entity, currentField);

            expect(result).toBe('product.abc123.name');
        });
    });

    describe('handleDeleteError', () => {
        it('should handle delete errors and add system errors and api errors', () => {
            const errors = [
                {
                    error: {
                        code: 'SOME_ERROR_CODE',
                        detail: '1',
                        parameters: {
                            '{{ parameter }}': 'Test Parameter',
                        },
                    },
                    entityName: 'Entity1',
                    id: '1',
                },
                {
                    error: {
                        code: 'SOME_ERROR_CODE',
                        detail: '2',
                        parameters: {
                            '{{ parameter }}': 'Test Parameter',
                        },
                    },
                    entityName: 'Entity2',
                    id: '2',
                },
            ];

            errorResolver.handleDeleteError(errors);

            expect(Cicada.State.dispatch).toHaveBeenCalledWith('error/addSystemError', {
                error: expect.any(Cicada.Classes.CicadaError),
            });
            expect(Cicada.State.dispatch).toHaveBeenCalledWith('error/addApiError', {
                expression: 'Entity1.1',
                error: expect.any(Cicada.Classes.CicadaError),
            });
            expect(Cicada.State.dispatch).toHaveBeenCalledWith('error/addApiError', {
                expression: 'Entity2.2',
                error: expect.any(Cicada.Classes.CicadaError),
            });
        });
    });
});
