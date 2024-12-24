/**
 * @package admin
 *
 * This is the initial start file for the whole administration. It loads
 * the Cicada Core with the Cicada object. And then starts to execute
 * the application.
 */
import { configureCompat } from 'vue';
import 'src/core/cicada';
import 'src/app/main';

// Take all keys out of Cicada.compatConfig but set them to true
const compatConfig = Object.fromEntries(
    Object.keys(Cicada.compatConfig).map((key) => [
        key,
        true,
    ]),
);

// eslint-disable-next-line @typescript-eslint/no-unsafe-call
configureCompat(compatConfig);
