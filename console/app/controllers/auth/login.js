import Controller, { inject as controller } from '@ember/controller';
import { inject as service } from '@ember/service';
import { tracked } from '@glimmer/tracking';
import { action } from '@ember/object';
import { later, cancel } from '@ember/runloop';
import pathToRoute from '@fleetbase/ember-core/utils/path-to-route';
import config from '@fleetbase/console/config/environment';

export default class AuthLoginController extends Controller {
    @controller('auth.forgot-password') forgotPasswordController;
    @service notifications;
    @service urlSearchParams;
    @service session;
    @service router;
    @service intl;
    @service fetch;

    get isDemoMode() {
        return config.APP.demoMode === true;
    }

    /**
     * Whether or not to remember the users session
     *
     * @var {Boolean}
     */
    @tracked rememberMe = false;

    @tracked _identity = null;
    @tracked _password = null;

    get identity() {
        if (this._identity === null && this.isDemoMode) {
            return 'admin@kantrack.ec';
        }
        return this._identity;
    }

    set identity(value) {
        this._identity = value;
    }

    get password() {
        if (this._password === null && this.isDemoMode) {
            return 'KanTrack2026!';
        }
        return this._password;
    }

    set password(value) {
        this._password = value;
    }

    /**
     * True while the demo database reset is in progress.
     *
     * @var {Boolean}
     */
    @tracked isResetting = false;

    /**
     * True when the demo account's 2-day window has expired.
     *
     * @var {Boolean}
     */
    @tracked isDemoAccountExpired = false;

    _resetPollTimer = null;

    /**
     * Begin polling installer/initialize until shouldOnboard is false,
     * meaning the seeder has finished creating demo data.
     */
    startResetCheck() {
        if (localStorage.getItem('kantrack-demo-resetting') !== 'true') {
            return;
        }
        this.isResetting = true;
        this._schedulePoll();
    }

    _schedulePoll() {
        this._resetPollTimer = later(this, this._pollReset, 5000);
    }

    async _pollReset() {
        try {
            const { shouldOnboard } = await this.fetch.get('installer/initialize');
            if (!shouldOnboard) {
                localStorage.removeItem('kantrack-demo-resetting');
                this.isResetting = false;
                return;
            }
        } catch (_) {
            // API not yet ready — keep polling
        }
        this._schedulePoll();
    }

    stopResetCheck() {
        if (this._resetPollTimer) {
            cancel(this._resetPollTimer);
            this._resetPollTimer = null;
        }
    }

    /**
     * Login is validating user input
     *
     * @var {Boolean}
     */
    @tracked isValidating = false;

    /**
     * Login is processing
     *
     * @var {Boolean}
     */
    @tracked isLoading = false;

    /**
     * If the connection or requesst it taking too long
     *
     * @var {Boolean}
     */
    @tracked isSlowConnection = false;

    /**
     * Interval to determine when to timeout the request
     *
     * @var {Integer}
     */
    @tracked timeout = null;

    /**
     * Number of failed login attempts
     *
     * @var {Integer}
     */
    @tracked failedAttempts = 0;

    /**
     * Authentication token.
     *
     * @memberof AuthLoginController
     */
    @tracked token;

    /**
     * Action to login user.
     *
     * @param {Event} event
     * @return {void}
     * @memberof AuthLoginController
     */
    @action async login(event) {
        // firefox patch
        event.preventDefault();

        // Do not attempt login while the demo system is resetting
        if (this.isResetting) {
            return;
        }

        // get user credentials
        const { identity, password, rememberMe } = this;

        // If no password error
        if (!identity) {
            return this.notifications.warning(this.intl.t('auth.login.no-identity-notification'));
        }

        // If no password error
        if (!password) {
            return this.notifications.warning(this.intl.t('auth.login.no-identity-notification'));
        }

        // start loader
        this.set('isLoading', true);
        // set where to redirect on login
        this.setRedirect();

        // send request to check for 2fa
        try {
            let { twoFaSession, isTwoFaEnabled } = await this.session.checkForTwoFactor(identity);

            if (isTwoFaEnabled) {
                return this.session.store
                    .persist({ identity })
                    .then(() => {
                        return this.router.transitionTo('auth.two-fa', { queryParams: { token: twoFaSession } }).then(() => {
                            this.reset('success');
                        });
                    })
                    .catch((error) => {
                        this.notifications.serverError(error);
                        this.reset('error');

                        throw error;
                    });
            }
        } catch (error) {
            return this.notifications.serverError(error);
        }

        try {
            await this.session.authenticate('authenticator:fleetbase', { identity, password }, rememberMe);
        } catch (error) {
            this.failedAttempts++;

            // Handle unverified user
            if (error.toString().includes('not verified')) {
                return this.sendUserForEmailVerification(identity);
            }

            // Handle password reset required
            if (error.toString().includes('reset required')) {
                return this.sendUserForPasswordReset(identity);
            }

            // In demo mode, "no_user" means the reset job is still seeding
            if (this.isDemoMode && error?.code === 'no_user') {
                this.isLoading = false;
                localStorage.setItem('kantrack-demo-resetting', 'true');
                this.startResetCheck();
                return;
            }

            // Individual demo account has passed its 2-day lifetime
            if (error?.code === 'demo_account_expired') {
                this.isLoading = false;
                this.isDemoAccountExpired = true;
                return;
            }

            return this.failure(error);
        }

        if (this.session.isAuthenticated) {
            this.success();
        }
    }

    /**
     * Transition user to onboarding screen
     */
    @action transitionToOnboard() {
        return this.router.transitionTo('onboard');
    }

    /**
     * Transition to forgot password screen, if email is set - set it.
     */
    @action forgotPassword() {
        return this.router.transitionTo('auth.forgot-password').then(() => {
            if (this.email) {
                this.forgotPasswordController.email = this.email;
            }
        });
    }

    /**
     * Creates an email verification session and transitions user to verification route.
     *
     * @param {String} email
     * @return {Promise<Transition>}
     * @memberof AuthLoginController
     */
    @action sendUserForEmailVerification(email) {
        return this.fetch.post('auth/create-verification-session', { email, send: true }).then(({ token, session }) => {
            return this.session.store.persist({ email }).then(() => {
                this.notifications.warning(this.intl.t('auth.login.unverified-notification'));
                return this.router.transitionTo('auth.verification', { queryParams: { token, hello: session } }).then(() => {
                    this.reset('error');
                });
            });
        });
    }

    /**
     * Sends user to forgot password flow.
     *
     * @param {String} email
     * @return {Promise<Transition>}
     * @memberof AuthLoginController
     */
    @action sendUserForPasswordReset(email) {
        this.notifications.warning(this.intl.t('auth.login.password-reset-required'));
        return this.router.transitionTo('auth.forgot-password', { queryParams: { email } }).then(() => {
            this.reset('error');
        });
    }

    /**
     * Sets correct route to send user to after login.
     *
     * @void
     */
    setRedirect() {
        const shift = this.urlSearchParams.get('shift');

        if (shift) {
            this.session.setRedirect(pathToRoute(shift));
        }
    }

    /**
     * Handles the authentication success
     *
     * @void
     */
    success() {
        this.reset('success');
    }

    /**
     * Handles the authentication failure
     *
     * @param {String} error An error message
     * @void
     */
    failure(error) {
        this.notifications.serverError(error);
        this.reset('error');
    }

    /**
     * Handles the request slow connection
     *
     * @void
     */
    slowConnection() {
        this.notifications.error(this.intl.t('auth.login.slow-connection-message'));
    }

    /**
     * Reset the login form
     *
     * @param {String} type
     * @void
     */
    reset(type) {
        // reset login form state
        this.isLoading = false;
        this.isSlowConnection = false;
        // reset login form state depending on type of reset
        switch (type) {
            case 'success':
                this.identity = null;
                this.password = null;
                this.isValidating = false;
                break;
            case 'error':
            case 'fail':
                this.password = null;
                break;
        }
        // clearTimeout(this.timeout);
    }
}
