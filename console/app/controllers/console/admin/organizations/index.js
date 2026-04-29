import Controller from '@ember/controller';
import { tracked } from '@glimmer/tracking';
import { inject as service } from '@ember/service';
import { action } from '@ember/object';


/**
 * Controller for managing organizations in the admin console.
 *
 * @class ConsoleAdminOrganizationsController
 * @extends Controller
 */
export default class ConsoleAdminOrganizationsController extends Controller {
    @service store;
    @service intl;
    @service router;
    @service filters;
    @service crud;
    @service fetch;
    @service notifications;
    @service modalsManager;

    /**
     * The search query param value.
     *
     * @var {String|null}
     */
    @tracked query;

    /**
     * The current page of data being viewed
     *
     * @var {Integer}
     */
    @tracked page = 1;

    /**
     * The maximum number of items to show per page
     *
     * @var {Integer}
     */
    @tracked limit = 15;

    /**
     * The filterable param `sort`
     *
     * @var {String|Array}
     */
    @tracked sort = '-created_at';

    /**
     * The filterable param `name`
     *
     * @var {String}
     */
    @tracked name;

    /**
     * The filterable param `country`
     *
     * @var {String}
     */
    @tracked country;

    /**
     * Array to store the fetched companies.
     *
     * @var {Array}
     */
    @tracked companies = [];
    @tracked table;

    /**
     * Queryable parameters for this controller's model
     *
     * @var {Array}
     */
    queryParams = ['name', 'page', 'limit', 'sort'];

    /**
     * Columns for organization
     *
     * @memberof ConsoleAdminOrganizationsController
     */
    get columns() {
        return [
            {
                label: this.intl.t('common.name'),
                valuePath: 'name',
                cellComponent: 'table/cell/anchor',
                action: this.goToCompany,
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: this.intl.t('console.admin.organizations.index.owner-name-column'),
                valuePath: 'owner.name',
                width: '200px',
                resizable: true,
                sortable: true,
            },
            {
                label: this.intl.t('console.admin.organizations.index.owner-email-column'),
                valuePath: 'owner.email',
                width: '200px',
                resizable: true,
                sortable: true,
                filterable: true,
            },
            {
                label: this.intl.t('console.admin.organizations.index.phone-column'),
                valuePath: 'owner.phone',
                width: '200px',
                resizable: true,
                sortable: true,
                filterable: true,
                filterComponent: 'filter/string',
            },
            {
                label: this.intl.t('console.admin.organizations.index.users-count-column'),
                valuePath: 'users_count',
                resizable: true,
                sortable: true,
            },
            {
                label: this.intl.t('common.created-at'),
                valuePath: 'createdAt',
            },
            {
                label: this.intl.t('admin.organizations.index.plan-column'),
                valuePath: 'subscription_plan_name',
                width: '120px',
                cellComponent: 'table/cell/base',
            },
            {
                label: this.intl.t('admin.organizations.index.subscription-status-column'),
                valuePath: 'subscription_status',
                width: '140px',
                cellComponent: 'table/cell/base',
            },
            {
                label: '',
                cellComponent: 'table/cell/dropdown',
                ddButtonText: false,
                ddButtonIcon: 'ellipsis-h',
                ddButtonIconPrefix: 'fas',
                ddMenuLabel: this.intl.t('admin.organizations.index.organization-actions-label'),
                cellClassNames: 'overflow-visible',
                wrapperClass: 'flex items-center justify-end mx-2',
                sticky: 'right',
                width: 60,
                actions: [
                    {
                        label: this.intl.t('admin.organizations.index.edit-action'),
                        icon: 'edit',
                        fn: this.editOrganization,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: this.intl.t('admin.organizations.index.assign-plan-action'),
                        icon: 'tags',
                        fn: this.managePlan,
                    },
                    {
                        label: this.intl.t('admin.organizations.index.register-payment-action'),
                        icon: 'dollar-sign',
                        fn: this.registerPayment,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: this.intl.t('admin.organizations.index.payment-history-action'),
                        icon: 'receipt',
                        fn: this.viewPaymentHistory,
                    },
                    {
                        label: this.intl.t('admin.organizations.index.subscription-history-action'),
                        icon: 'history',
                        fn: this.viewSubscriptionHistory,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: this.intl.t('admin.organizations.index.suspend-action'),
                        icon: 'ban',
                        fn: this.suspendOrganization,
                    },
                    {
                        label: this.intl.t('admin.organizations.index.activate-action'),
                        icon: 'circle-check',
                        fn: this.activateOrganization,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: this.intl.t('admin.organizations.index.resource-usage-action'),
                        icon: 'chart-bar',
                        fn: this.viewResourceUsage,
                    },
                    {
                        separator: true,
                    },
                    {
                        label: this.intl.t('admin.organizations.index.delete-action'),
                        icon: 'trash',
                        fn: this.deleteOrganization,
                    },
                ],
                sortable: false,
                filterable: false,
                resizable: false,
                searchable: false,
            },
        ];
    }

    /**
     * Update search query param and reset page to 1
     *
     * @param {Event} event
     * @memberof ConsoleAdminOrganizationsController
     */
    @action createOrganization() {
        this.modalsManager.show('modals/edit-organization', {
            title: this.intl.t('admin.organizations.index.new-title'),
            acceptButtonText: this.intl.t('common.create'),
            acceptButtonIcon: 'plus',
            showAdminUser: true,
            organization: {
                name: null,
                description: null,
                phone: null,
                currency: 'USD',
                country: null,
                timezone: null,
            },
            adminUser: {
                name: null,
                email: null,
                password: null,
            },
            confirm: async (modal) => {
                modal.startLoading();
                const { name, description, phone, currency, country, timezone } = modal.getOption('organization');
                const { name: admin_name, email: admin_email, password: admin_password } = modal.getOption('adminUser');
                try {
                    await this.fetch.post('auth/create-organization', {
                        name,
                        description,
                        phone,
                        currency,
                        country,
                        timezone,
                        admin_name,
                        admin_email,
                        admin_password,
                    });
                    this.notifications.success(this.intl.t('admin.organizations.index.org-created-notification'));
                    modal.done();
                    return this.router.refresh();
                } catch (error) {
                    modal.stopLoading();
                    return this.notifications.serverError(error);
                }
            },
        });
    }

    @action search(event) {
        this.query = event.target.value ?? '';
        this.page = 1;
    }

    /**
     * Navigates to the organization-users route for the selected company.
     *
     * @method goToCompany
     * @param {Object} company - The selected company.
     */
    @action goToCompany(company) {
        this.router.transitionTo('console.admin.organizations.index.users', company.public_id);
    }

    /**
     * Toggles dialog to export `drivers`
     *
     * @void
     */
    @action exportOrganization() {
        const selections = this.table.selectedRows.map((_) => _.id);
        this.crud.export('companies', { params: { selections } });
    }

    @action async managePlan(company) {
        let subscription = null;
        let plans = [];

        try {
            const [subResponse, plansResponse] = await Promise.all([
                this.fetch.get(`admin/companies/${company.public_id}/subscription`).catch(() => ({})),
                this.fetch.get('admin/plans'),
            ]);
            subscription = subResponse.subscription ?? null;
            plans = plansResponse.plans ?? [];
        } catch (e) {
            this.notifications.serverError(e);
            return;
        }

        this.modalsManager.show('modals/assign-plan', {
            title: this.intl.t('admin.organizations.index.plan-modal-title', { name: company.name }),
            acceptButtonText: this.intl.t('common.save'),
            acceptButtonIcon: 'save',
            subscription,
            subscription_expires_display: subscription?.expires_at ? subscription.expires_at.substring(0, 10) : null,
            plans,
            form: {
                plan_slug: subscription?.plan_slug ?? '',
                billing_cycle: subscription?.billing_cycle ?? 'monthly',
                status: subscription?.status ?? 'active',
                extra_vehicles: subscription?.extra_vehicles ?? 0,
                expires_at: subscription?.expires_at ? subscription.expires_at.substring(0, 10) : '',
                notes: subscription?.notes ?? '',
            },
            confirm: async (modal) => {
                modal.startLoading();
                try {
                    const form = modal.getOption('form');
                    if (subscription) {
                        await this.fetch.put(`admin/companies/${company.public_id}/subscription`, form);
                    } else {
                        await this.fetch.post(`admin/companies/${company.public_id}/subscription`, form);
                    }
                    this.notifications.success(this.intl.t('admin.organizations.index.subscription-updated-notification'));
                    modal.done();
                    return this.router.refresh();
                } catch (error) {
                    this.notifications.serverError(error);
                }
            },
        });
    }

    @action async suspendOrganization(company) {
        this.modalsManager.confirm({
            title: `${this.intl.t('admin.organizations.index.suspend-action')}: ${company.name}`,
            body: this.intl.t('admin.organizations.index.suspend-confirm-body'),
            acceptButtonText: this.intl.t('admin.organizations.index.suspend-button'),
            acceptButtonScheme: 'danger',
            acceptButtonIcon: 'ban',
            confirm: async (modal) => {
                modal.startLoading();
                try {
                    await this.fetch.post(`admin/companies/${company.public_id}/suspend`);
                    this.notifications.warning(this.intl.t('admin.organizations.index.suspended-notification', { name: company.name }));
                    modal.done();
                    return this.router.refresh();
                } catch (error) {
                    this.notifications.serverError(error);
                }
            },
        });
    }

    @action async activateOrganization(company) {
        this.modalsManager.confirm({
            title: `${this.intl.t('admin.organizations.index.activate-action')}: ${company.name}`,
            body: this.intl.t('admin.organizations.index.activate-confirm-body'),
            acceptButtonText: this.intl.t('admin.organizations.index.activate-button'),
            acceptButtonIcon: 'circle-check',
            confirm: async (modal) => {
                modal.startLoading();
                try {
                    await this.fetch.post(`admin/companies/${company.public_id}/activate`);
                    this.notifications.success(this.intl.t('admin.organizations.index.activated-notification', { name: company.name }));
                    modal.done();
                    return this.router.refresh();
                } catch (error) {
                    this.notifications.serverError(error);
                }
            },
        });
    }

    @action async registerPayment(company) {
        let subscription = null;

        try {
            const res = await this.fetch.get(`admin/companies/${company.public_id}/subscription`).catch(() => ({}));
            subscription = res.subscription ?? null;
        } catch (e) {
            this.notifications.serverError(e);
            return;
        }

        const today = new Date().toISOString().substring(0, 10);

        this.modalsManager.show('modals/register-payment', {
            title: this.intl.t('admin.organizations.index.register-payment-title', { name: company.name }),
            acceptButtonText: this.intl.t('common.save'),
            acceptButtonIcon: 'dollar-sign',
            subscription,
            subscription_expires_display: subscription?.expires_at ? subscription.expires_at.substring(0, 10) : null,
            form: {
                amount: subscription?.plan?.price_monthly ?? '',
                currency: 'USD',
                payment_method: 'bank_transfer',
                billing_cycle: subscription?.billing_cycle ?? 'monthly',
                reference: '',
                paid_at: today,
                period_end: '',
                notes: '',
            },
            confirm: async (modal) => {
                modal.startLoading();
                try {
                    const form = modal.getOption('form');
                    await this.fetch.post(`admin/companies/${company.public_id}/payments`, form);
                    this.notifications.success(this.intl.t('admin.organizations.index.payment-registered-notification'));
                    modal.done();
                    return this.router.refresh();
                } catch (error) {
                    modal.stopLoading();
                    return this.notifications.serverError(error);
                }
            },
        });
    }

    @action async viewPaymentHistory(company) {
        let payments = [];

        try {
            const res = await this.fetch.get(`admin/companies/${company.public_id}/payments`);
            payments = res.payments ?? [];
        } catch (e) {
            this.notifications.serverError(e);
            return;
        }

        this.modalsManager.show('modals/payment-history', {
            title: this.intl.t('admin.organizations.index.payment-history-title', { name: company.name }),
            hideAcceptButton: true,
            declineButtonText: this.intl.t('common.close'),
            payments,
        });
    }

    @action async viewSubscriptionHistory(company) {
        let history = [];

        try {
            const res = await this.fetch.get(`admin/companies/${company.public_id}/subscription-history`);
            history = res.history ?? [];
        } catch (e) {
            this.notifications.serverError(e);
            return;
        }

        this.modalsManager.show('modals/subscription-history', {
            title: this.intl.t('admin.organizations.index.subscription-history-title', { name: company.name }),
            hideAcceptButton: true,
            declineButtonText: this.intl.t('common.close'),
            history,
        });
    }

    @action editOrganization(company) {
        this.modalsManager.show('modals/edit-organization', {
            title: this.intl.t('admin.organizations.index.edit-modal-title', { name: company.name }),
            acceptButtonText: this.intl.t('common.save'),
            acceptButtonIcon: 'save',
            showAdminUser: true,
            isEditing: true,
            organization: {
                name: company.name,
                description: company.description,
                phone: company.phone,
                currency: company.currency,
            },
            adminUser: {
                name: company.owner?.name ?? '',
                email: company.owner?.email ?? '',
                password: null,
            },
            confirm: async (modal) => {
                modal.startLoading();
                const { name, description, phone, currency } = modal.getOption('organization');
                const { name: admin_name, email: admin_email, password: admin_password } = modal.getOption('adminUser');
                try {
                    const payload = { name, description, phone, currency, admin_name, admin_email };
                    if (admin_password) {
                        payload.admin_password = admin_password;
                    }
                    await this.fetch.put(`admin/companies/${company.public_id}`, payload);
                    this.notifications.success(this.intl.t('admin.organizations.index.org-updated-notification'));
                    modal.done();
                    return this.router.refresh();
                } catch (error) {
                    modal.stopLoading();
                    return this.notifications.serverError(error);
                }
            },
        });
    }

    @action deleteOrganization(company) {
        this.modalsManager.confirm({
            title: this.intl.t('admin.organizations.index.delete-modal-title', { name: company.name }),
            body: this.intl.t('admin.organizations.index.delete-confirm-body'),
            acceptButtonText: this.intl.t('common.delete'),
            acceptButtonScheme: 'danger',
            acceptButtonIcon: 'trash',
            confirm: async (modal) => {
                modal.startLoading();
                try {
                    await this.fetch.delete(`admin/companies/${company.public_id}`);
                    this.notifications.success(this.intl.t('admin.organizations.index.org-deleted-notification', { name: company.name }));
                    modal.done();
                    return this.router.refresh();
                } catch (error) {
                    this.notifications.serverError(error);
                }
            },
        });
    }

    @action async viewResourceUsage(company) {
        let usage = {};
        try {
            const response = await this.fetch.get(`admin/companies/${company.public_id}/resource-usage`);
            usage = response.usage ?? {};
        } catch (e) {
            this.notifications.serverError(e);
            return;
        }

        this.modalsManager.show('modals/resource-usage', {
            title: this.intl.t('admin.organizations.index.resource-usage-title', { name: company.name }),
            hideAcceptButton: true,
            declineButtonText: this.intl.t('common.close'),
            usage,
        });
    }
}
