/**
 * @package admin
 */

type ServiceObject = {
    get: <SN extends keyof ServiceContainer>(serviceName: SN) => ServiceContainer[SN];
    list: () => (keyof ServiceContainer)[];
    register: <SN extends keyof ServiceContainer>(serviceName: SN, service: ServiceContainer[SN]) => void;
    registerMiddleware: typeof Cicada.Application.addServiceProviderMiddleware;
    registerDecorator: typeof Cicada.Application.addServiceProviderDecorator;
};

/**
 * Return the ServiceObject (Cicada.Service().myService)
 * or direct access the services (Cicada.Service('myService')
 */
function serviceAccessor<SN extends keyof ServiceContainer>(serviceName: SN): ServiceContainer[SN];
function serviceAccessor(): ServiceObject;
function serviceAccessor<SN extends keyof ServiceContainer>(serviceName?: SN): ServiceContainer[SN] | ServiceObject {
    if (serviceName) {
        // eslint-disable-next-line @typescript-eslint/no-unsafe-return
        return Cicada.Application.getContainer('service')[serviceName];
    }

    const serviceObject: ServiceObject = {
        // eslint-disable-next-line @typescript-eslint/no-unsafe-return
        get: (name) => Cicada.Application.getContainer('service')[name],
        list: () => Cicada.Application.getContainer('service').$list(),
        register: (name, service) => Cicada.Application.addServiceProvider(name, service),
        registerMiddleware: (...args) => Cicada.Application.addServiceProviderMiddleware(...args),
        registerDecorator: (...args) => Cicada.Application.addServiceProviderDecorator(...args),
    };

    return serviceObject;
}

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default serviceAccessor;
