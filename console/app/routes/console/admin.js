import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class ConsoleAdminRoute extends Route {
    @service currentUser;
    @service notifications;
    @service router;

    beforeModel() {
        const user = this.currentUser.user;
        const snapshot = this.currentUser.userSnapshot;

        console.log('[AdminRoute] user object:', user);
        console.log('[AdminRoute] user.is_super_admin:', user?.is_super_admin);
        console.log('[AdminRoute] snapshot.is_super_admin:', snapshot?.is_super_admin);
        console.log('[AdminRoute] isSuperAdmin getter:', this.currentUser.isSuperAdmin);

        const isSuperAdmin = user?.is_super_admin === true
            || snapshot?.is_super_admin === true
            || this.currentUser.isSuperAdmin;

        console.log('[AdminRoute] final isSuperAdmin:', isSuperAdmin);

        if (!isSuperAdmin) {
            return this.router.transitionTo('console').then(() => {
                this.notifications.error('No tienes autorización para acceder al panel de administración.');
            });
        }
    }
}
