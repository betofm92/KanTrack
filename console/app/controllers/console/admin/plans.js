import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';

export default class ConsoleAdminPlansController extends Controller {
    @service fetch;
    @service notifications;
    @service modalsManager;
    @service router;

    @tracked table;

    get columns() {
        return [
            {
                label: 'Plan',
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
                label: 'Precio/mes',
                valuePath: 'price_monthly',
                width: '130px',
                cellComponent: 'table/cell/base',
            },
            {
                label: 'Precio/año',
                valuePath: 'price_annual',
                width: '130px',
                cellComponent: 'table/cell/base',
            },
            {
                label: 'Vehículos',
                valuePath: 'max_vehicles',
                width: '110px',
                cellComponent: 'table/cell/base',
            },
            {
                label: 'Conductores',
                valuePath: 'max_drivers',
                width: '110px',
                cellComponent: 'table/cell/base',
            },
            {
                label: 'Usuarios',
                valuePath: 'max_users',
                width: '100px',
                cellComponent: 'table/cell/base',
            },
            {
                label: 'Estado',
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
                ddMenuLabel: 'Acciones del plan',
                cellClassNames: 'overflow-visible',
                wrapperClass: 'flex items-center justify-end mx-2',
                width: '60px',
                actions: [
                    {
                        label: 'Editar plan',
                        icon: 'edit',
                        fn: this.editPlan,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: 'Eliminar plan',
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
            title: 'Nuevo Plan',
            acceptButtonText: 'Crear Plan',
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
                    this.notifications.success('Plan creado correctamente.');
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
            title: `Editar Plan: ${plan.name}`,
            acceptButtonText: 'Guardar',
            acceptButtonIcon: 'save',
            plan: Object.assign({}, plan),
            confirm: async (modal) => {
                modal.startLoading();
                try {
                    await this.fetch.put(`admin/plans/${plan.slug}`, modal.getOption('plan'));
                    this.notifications.success('Plan actualizado.');
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
            title: `Eliminar plan "${plan.name}"`,
            body: '¿Seguro que deseas eliminar este plan? No se puede eliminar si hay organizaciones suscritas.',
            acceptButtonText: 'Eliminar',
            acceptButtonScheme: 'danger',
            acceptButtonIcon: 'trash',
            confirm: async (modal) => {
                modal.startLoading();
                try {
                    await this.fetch.delete(`admin/plans/${plan.slug}`);
                    this.notifications.success('Plan eliminado.');
                    modal.done();
                    return this.router.refresh();
                } catch (error) {
                    this.notifications.serverError(error);
                }
            },
        });
    }
}
