import Application from '@ember/application';
import Resolver from 'ember-resolver';
import loadInitializers from 'ember-load-initializers';
import config from '@fleetbase/console/config/environment';
import loadExtensions from '@fleetbase/ember-core/utils/load-extensions';
import mapEngines from '@fleetbase/ember-core/utils/map-engines';
import loadRuntimeConfig from '@fleetbase/console/utils/runtime-config';
import applyRouterFix from './utils/router-refresh-patch';

const CORE_EXTENSIONS = [
    { name: '@fleetbase/dev-engine', fleetbase: { route: 'developers' } },
    { name: '@fleetbase/iam-engine', fleetbase: { route: 'iam' } },
    { name: '@fleetbase/fleetops-engine', fleetbase: { route: 'fleet-ops' } },
    { name: '@fleetbase/storefront-engine', fleetbase: { route: 'storefront' } },
    { name: '@fleetbase/registry-bridge-engine', fleetbase: { route: 'extensions' } },
];

export default class App extends Application {
    modulePrefix = config.modulePrefix;
    podModulePrefix = config.podModulePrefix;
    Resolver = Resolver;
    extensions = CORE_EXTENSIONS;
    engines = mapEngines(CORE_EXTENSIONS);

    async ready() {
        applyRouterFix(this);

        try {
            const indexedExtensions = await loadExtensions();
            const dedupedExtensions = new Map();

            for (let i = 0; i < CORE_EXTENSIONS.length; i++) {
                const extension = CORE_EXTENSIONS[i];
                dedupedExtensions.set(extension.name, extension);
            }

            for (let i = 0; i < indexedExtensions.length; i++) {
                const extension = indexedExtensions[i];
                dedupedExtensions.set(extension.name, extension);
            }

            const extensions = Array.from(dedupedExtensions.values());
            this.extensions = extensions;
            this.engines = mapEngines(extensions);
        } catch (error) {
            // Keep core engine dependency mappings available even if extensions index fails.
            this.extensions = CORE_EXTENSIONS;
            this.engines = mapEngines(CORE_EXTENSIONS);
        }
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadRuntimeConfig();
    loadInitializers(App, config.modulePrefix);

    let fleetbase = App.create();
    fleetbase.deferReadiness();
    fleetbase.boot();
});
