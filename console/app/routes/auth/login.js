import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';
import config from '@fleetbase/console/config/environment';

export default class AuthLoginRoute extends Route {
    @service session;
    @service universe;

    /**
     * If user is authentication redirect to console.
     *
     * @memberof AuthLoginRoute
     * @void
     */
    beforeModel(transition) {
        this.session.prohibitAuthentication('console');
        return this.universe.virtualRouteRedirect(transition, 'auth:login', 'virtual', { restoreQueryParams: true });
    }

    setupController(controller, model) {
        super.setupController(controller, model);
        if (config.APP.demoMode === true) {
            controller.identity = 'admin@kantrack.ec';
            controller.password = 'KanTrack2026!';
        }
        // If the demo reset job ran, poll until seeding is complete
        controller.startResetCheck();
    }

    deactivate() {
        // Stop polling when user leaves the login page (e.g. successful login)
        this.controllerFor('auth.login').stopResetCheck();
    }
}
