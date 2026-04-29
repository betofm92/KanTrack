import Model, { attr, belongsTo } from '@ember-data/model';

export default class CompanySubscriptionModel extends Model {
    @attr('string') uuid;
    @attr('string') company_uuid;
    @attr('string') plan_slug;
    @attr('string') billing_cycle;
    @attr('number') extra_vehicles;
    @attr('string') starts_at;
    @attr('string') expires_at;
    @attr('string') status;
    @attr('string') notes;
    @attr('string') created_at;
    @attr('raw') plan;

    get isActive() {
        return this.status === 'active' || this.status === 'trial';
    }

    get isSuspended() {
        return this.status === 'suspended';
    }

    get statusLabel() {
        const labels = {
            active: 'Activo',
            trial: 'Trial',
            suspended: 'Suspendido',
            expired: 'Vencido',
            cancelled: 'Cancelado',
        };
        return labels[this.status] ?? this.status;
    }

    get statusColor() {
        const colors = {
            active: 'green',
            trial: 'blue',
            suspended: 'red',
            expired: 'yellow',
            cancelled: 'gray',
        };
        return colors[this.status] ?? 'gray';
    }
}
