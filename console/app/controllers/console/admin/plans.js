import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class ConsoleAdminPlansController extends Controller {
    @service fetch;
    @service notifications;
    @service modalsManager;
    @service router;
    @service intl;

    @tracked table;

    get columns() {
        return [
            {
                label: this.intl.t('admin.plans.index.plan-column'),
                valuePath: 'name',
                resizable: true,
                sortable: true,
                cellComponent: 'table/cell/base',
            },
            {
                label: 'Slug',
                valuePath: 'slug',
                width: '130px',
                resizable: true,
                cellComponent: 'table/cell/base',
            },
            {
                label: this.intl.t('admin.plans.index.price-month-column'),
                valuePath: 'price_monthly',
                width: '130px',
                cellComponent: 'table/cell/base',
            },
            {
                label: this.intl.t('admin.plans.index.price-year-column'),
                valuePath: 'price_annual',
                width: '130px',
                cellComponent: 'table/cell/base',
            },
            {
                label: this.intl.t('admin.plans.index.vehicles-column'),
                valuePath: 'max_vehicles',
                width: '110px',
                cellComponent: 'table/cell/base',
            },
            {
                label: this.intl.t('admin.plans.index.drivers-column'),
                valuePath: 'max_drivers',
                width: '110px',
                cellComponent: 'table/cell/base',
            },
            {
                label: this.intl.t('admin.plans.index.users-column'),
                valuePath: 'max_users',
                width: '100px',
                cellComponent: 'table/cell/base',
            },
            {
                label: this.intl.t('admin.plans.index.status-column'),
                valuePath: 'is_active',
                width: '100px',
                cellComponent: 'table/cell/base',
            },
            {
                label: '',
                cellComponent: 'table/cell/dropdown',
                ddButtonText: false,
                ddButtonIcon: 'ellipsis-h',
                ddButtonIconPrefix: 'fas',
                ddMenuLabel: this.intl.t('admin.plans.index.plan-actions-label'),
                cellClassNames: 'overflow-visible',
                wrapperClass: 'flex items-center justify-end mx-2',
                width: '60px',
                actions: [
                    {
                        label: this.intl.t('admin.plans.index.edit-action'),
                        icon: 'edit',
                        fn: this.editPlan,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: this.intl.t('admin.plans.index.delete-action'),
                        icon: 'trash',
                        fn: this.deletePlan,
                    },
                ],
                sortable: false,
                filterable: false,
                resizable: false,
                searchable: false,
            },
        ];
    }

    @action createPlan() {
        this.modalsManager.show('modals/plan-form', {
            title: this.intl.t('admin.plans.index.new-title'),
            acceptButtonText: this.intl.t('admin.plans.index.create-button'),
            acceptButtonIcon: 'plus',
            plan: {
                slug: '',
                name: '',
                description: '',
                price_monthly: 0,
                price_annual: 0,
                max_vehicles: null,
                max_users: null,
                max_drivers: null,
                max_places: null,
                max_customers: null,
                gps_interval_seconds: 30,
                has_api: false,
                has_webhooks: false,
                has_reports: false,
                has_driver_management: false,
                has_advanced_analytics: false,
                is_custom: false,
                is_active: true,
                sort_order: 0,
            },
            confirm: async (modal) => {
                modal.startLoading();
                try {
                    await this.fetch.post('admin/plans', modal.getOption('plan'));
                    this.notifications.success(this.intl.t('admin.plans.index.plan-created-notification'));
                    modal.done();
                    return this.router.refresh();
                } catch (error) {
                    this.notifications.serverError(error);
                }
            },
        });
    }

    @action editPlan(plan) {
        this.modalsManager.show('modals/plan-form', {
            title: this.intl.t('admin.plans.index.edit-modal-title', { name: plan.name }),
            acceptButtonText: this.intl.t('common.save'),
            acceptButtonIcon: 'save',
            plan: Object.assign({}, plan),
            confirm: async (modal) => {
                modal.startLoading();
                try {
                    await this.fetch.put(`admin/plans/${plan.slug}`, modal.getOption('plan'));
                    this.notifications.success(this.intl.t('admin.plans.index.plan-updated-notification'));
                    modal.done();
                    return this.router.refresh();
                } catch (error) {
                    this.notifications.serverError(error);
                }
            },
        });
    }

    @action deletePlan(plan) {
        this.modalsManager.confirm({
            title: this.intl.t('admin.plans.index.delete-modal-title', { name: plan.name }),
            body: this.intl.t('admin.plans.index.delete-confirm-body'),
            acceptButtonText: this.intl.t('common.delete'),
            acceptButtonScheme: 'danger',
            acceptButtonIcon: 'trash',
            confirm: async (modal) => {
                modal.startLoading();
                try {
                    await this.fetch.delete(`admin/plans/${plan.slug}`);
                    this.notifications.success(this.intl.t('admin.plans.index.plan-deleted-notification'));
                    modal.done();
                    return this.router.refresh();
                } catch (error) {
                    this.notifications.serverError(error);
                }
            },
        });
    }
}
