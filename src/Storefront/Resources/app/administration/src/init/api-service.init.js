import ThemeService from '../core/service/api/theme.api.service';

const { Application } = Cicada;

Cicada.Service().register('themeService', (container) => {
    const initContainer = Application.getContainer('init');
    return new ThemeService(initContainer.httpClient, container.loginService);
});
