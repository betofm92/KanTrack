import Route from '@ember/routing/route';
import { inject as service } from '@ember/service';

export default class ConsoleAdminPlansRoute extends Route {
    @service fetch;

    async model() {
        try {
            const response = await this.fetch.get('admin/plans');
            return response?.plans ?? [];
        } catch (e) {
            return [];
        }
    }
}
