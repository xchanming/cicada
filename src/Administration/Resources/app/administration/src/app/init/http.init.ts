/**
 * @package admin
 */
import type { AxiosInstance } from 'axios';

const HttpClient = Cicada.Classes._private.HttpFactory;

// eslint-disable-next-line sw-deprecation-rules/private-feature-declarations
export default function initializeHttpClient(): AxiosInstance {
    return HttpClient(Cicada.Context.api) as unknown as AxiosInstance;
}
