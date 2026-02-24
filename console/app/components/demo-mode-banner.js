import Component from '@glimmer/component';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';
import { get } from '@ember/object';
import { later, cancel, schedule } from '@ember/runloop';
import config from '@fleetbase/console/config/environment';

export default class DemoModeBannerComponent extends Component {
    @service session;
    @service notifications;

    @tracked secondsRemaining = null;
    @tracked isResetting = false;

    _timer = null;

    // config.APP.demoMode is a static build-time value (no tracking needed).
    // session.isAuthenticated IS properly @tracked by Ember Simple Auth,
    // so the banner re-evaluates correctly on reload and route transitions.
    get isDemoMode() {
        return config.APP.demoMode === true && this.session.isAuthenticated === true;
    }

    get expiresAt() {
        const raw = get(this.session, 'data.authenticated.expires_at');
        return raw ? new Date(raw) : null;
    }

    get formattedCountdown() {
        if (this.secondsRemaining === null || this.secondsRemaining < 0) {
            return '0:00';
        }
        const m = Math.floor(this.secondsRemaining / 60);
        const s = this.secondsRemaining % 60;
        return `${m}:${s.toString().padStart(2, '0')}`;
    }

    get countdownClass() {
        if (this.secondsRemaining === null) return '';
        if (this.secondsRemaining <= 60) return 'text-red-400 font-bold animate-pulse';
        if (this.secondsRemaining <= 180) return 'text-yellow-400 font-semibold';
        return 'text-green-400';
    }

    @action
    startCountdown() {
        schedule('afterRender', this, () => {
            if (!this.isDemoMode || !this.expiresAt) {
                return;
            }
            localStorage.setItem('kantrack-is-demo', 'true');
            this._tick();
        });
    }

    _tick() {
        const now = new Date();
        const diff = Math.round((this.expiresAt - now) / 1000);
        this.secondsRemaining = Math.max(0, diff);

        if (this.secondsRemaining <= 0) {
            this.isResetting = true;
            localStorage.setItem('kantrack-demo-resetting', 'true');
            later(this, () => {
                this.session.invalidateWithLoader('Sesión demo finalizada. Reiniciando sistema...');
            }, 2000);
            return;
        }

        this._timer = later(this, this._tick, 1000);
    }

    willDestroy() {
        super.willDestroy(...arguments);
        if (this._timer) {
            cancel(this._timer);
        }
    }
}
