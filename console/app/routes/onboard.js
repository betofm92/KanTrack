import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';
import config from '@fleetbase/console/config/environment';

export default class OnboardRoute extends Route {
    @service router;

    beforeModel() {
        if (config.APP.demoMode === true) {
            return this.router.transitionTo('auth.login');
        }
    }
}
