import Model, { attr } from '@ember-data/model';

export default class PlanModel extends Model {
    @attr('string') uuid;
    @attr('string') slug;
    @attr('string') name;
    @attr('string') description;
    @attr('number') price_monthly;
    @attr('number') price_annual;
    @attr('number') max_vehicles;
    @attr('number') max_users;
    @attr('number') max_drivers;
    @attr('number') max_places;
    @attr('number') max_customers;
    @attr('number') gps_interval_seconds;
    @attr('boolean') has_api;
    @attr('boolean') has_webhooks;
    @attr('boolean') has_reports;
    @attr('boolean') has_driver_management;
    @attr('boolean') has_advanced_analytics;
    @attr('boolean') is_custom;
    @attr('boolean') is_active;
    @attr('number') sort_order;
    @attr('string') created_at;
    @attr('string') updated_at;
}
