import Component from '@glimmer/component';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action, getProperties } from '@ember/object';
import { isBlank } from '@ember/utils';
import { task } from 'ember-concurrency';
import OnboardValidations from '../../validations/onboard';
import lookupValidator from 'ember-changeset-validations';
import Changeset from 'ember-changeset';
import config from '@fleetbase/console/config/environment';

export default class OnboardingFormComponent extends Component {
    @service fetch;
    @service session;
    @service router;
    @service notifications;
    @service urlSearchParams;
    @tracked name;
    @tracked email;
    @tracked phone;
    @tracked organization_name;
    @tracked password;
    @tracked password_confirmation;
    @tracked error;

    get isDemoMode() {
        return config.APP.demoMode === true;
    }

    get filled() {
        if (this.isDemoMode) {
            // eslint-disable-next-line ember/no-get
            const input = getProperties(this, 'name', 'email', 'password', 'password_confirmation');
            return Object.values(input).every((val) => !isBlank(val));
        }
        // eslint-disable-next-line ember/no-get
        const input = getProperties(this, 'name', 'email', 'phone', 'organization_name', 'password', 'password_confirmation');
        return Object.values(input).every((val) => !isBlank(val));
    }

    @task *onboard(event) {
        event?.preventDefault?.();

        // eslint-disable-next-line ember/no-get
        const input = getProperties(this, 'name', 'email', 'phone', 'organization_name', 'password', 'password_confirmation');

        // In demo mode, supply a dummy org name to satisfy the changeset validator
        // (the backend auto-generates the real name from the user's first name)
        if (this.isDemoMode && isBlank(input.organization_name)) {
            input.organization_name = (input.name || '').split(' ')[0] + ' KanTrack Demo';
        }

        const changeset = new Changeset(input, lookupValidator(OnboardValidations), OnboardValidations);

        yield changeset.validate();

        if (changeset.get('isInvalid')) {
            const errorMessage = changeset.errors.firstObject.validation.firstObject;

            this.notifications.error(errorMessage);
            return;
        }

        // Set user timezone
        input.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        try {
            const { status, skipVerification, token, session } = yield this.fetch.post('onboard/create-account', input);
            if (status !== 'success') {
                this.notifications.error('Onboard failed');
                return;
            }

            // save session
            this.args.context.persist('session', session);

            if (skipVerification === true && token) {
                // only manually authenticate if skip verification
                this.session.isOnboarding().manuallyAuthenticate(token);

                yield this.router.transitionTo('console');
                return this.notifications.success('Welcome to Fleetbase!');
            } else {
                this.args.orchestrator.next();
                this.urlSearchParams.setParamsToCurrentUrl({
                    step: this.args.orchestrator?.current?.id,
                    session,
                });
            }
        } catch (err) {
            this.notifications.serverError(err);
        }
    }
}
