import Model, { attr } from '@ember-data/model';
import { computed } from '@ember/object';
import { capitalize } from '@ember/string';
import { inject as service } from '@ember/service';
import { pluralize } from 'ember-inflector';
import { format, formatDistanceToNow } from 'date-fns';
import humanize from '@fleetbase/ember-core/utils/humanize';

export const parserPermissionName = function (permissionName, index = 0) {
    const parts = permissionName.split(' ');

    if (parts.length >= index + 1) {
        return parts[index];
    }

    return null;
};

export const getPermissionExtension = function (permissionName) {
    return parserPermissionName(permissionName);
};

export const getPermissionAction = function (permissionName) {
    return parserPermissionName(permissionName, 1);
};

export const getPermissionTextoMostrar = function (permissionName) {
    return parserPermissionName(permissionName, 1);
};

export const getPermissionResource = function (permissionName) {
    return parserPermissionName(permissionName, 2);
};

const titleize = function (string = '') {
    if (typeof string !== 'string') {
        return '';
    }
    return humanize(string)
        .split(' ')
        .map((w) => capitalize(w))
        .join(' ');
};

const smartTitleize = function (string = '') {
    if (typeof string !== 'string') {
        return '';
    }

    let titleized = titleize(string);
    if (titleized === 'Iam') {
        titleized = titleized.toUpperCase();
    }

    return titleized;
};

/**
 * Permission model for handling and authorizing actions.
 * permission schema: {extension} {action} {resource}
 * action and resource can be wildcards
 *
 * @export
 * @class PermissionModel
 * @extends {Model}
 */
export default class PermissionModel extends Model {
    /** @attributes */
    @attr('string') name;
    @attr('string') guard_name;
    @attr('string') service;

    /** @dates */
    @attr('date') created_at;
    @attr('date') updated_at;

    /** @methods */
    toJSON() {
        return {
            name: this.name,
            textoMostrar: this.textoMostrar,
            guard_name: this.guard_name,
            service: this.service,
            created_at: this.created_at,
            updated_at: this.updated_at,
        };
    }

    /** @computed */
    @computed('name') get serviceName() {
        return getPermissionExtension(this.name) + ' Service';
    }

    @computed('name') get extensionName() {
        return getPermissionExtension(this.name);
    }

    @computed('name') get actionName() {
        let action = getPermissionAction(this.name);

        if (action === '*') {
            return 'all';
        }

        if (action === 'see') {
            return 'ver';
        }

        return titleize(action);
    }

    @computed('name') get resourceName() {
        //console.log('resourceName ', this.name);

        const splitName = this.name.split(' ');

        //console.log('splitName ', splitName);

        const actionKey = splitName[1]?.toLowerCase() ?? 'unknown';
        const resourceKey = splitName[2]?.toLowerCase() ?? 'unknown';
        const extensionKey = splitName[0]?.toLowerCase() ?? 'unknown';

        const action = this.intl.t(`common.${actionKey}`);
        const resource = this.intl.t(`resource.${resourceKey}`);
        const extension = this.intl.t(`resource.${extensionKey}`);

        //const action = this.intl.t(`common.${actionKey}`);

        //console.log('nombre permiso ', actionKey, resourceKey, extensionKey);
        //console.log('trad resourceName ', action, resource, extension);


        return getPermissionResource(this.name);

        //return `${action} ${resource} ${extension}`;
    }

    /*@computed('actionName', 'name', 'resourceName', 'extensionName') get description() {
        let actionName = this.actionName;
        let actionPreposition = 'to';
        let resourceName = pluralize(smartTitleize(this.resourceName));
        let resourcePreposition = getPermissionAction(this.name) === '*' && resourceName ? 'with' : '';
        let extensionName = smartTitleize(this.extensionName);
        let extensionPreposition = 'on';
        let descriptionParts = ['Permission', actionPreposition, actionName, resourcePreposition, resourceName, extensionPreposition, extensionName];

        return descriptionParts.join(' ');
    }*/

    @service intl;

    @computed('actionName', 'name', 'resourceName', 'extensionName') get description() {
        const actionKey = this.actionName?.toLowerCase() ?? 'unknown';
        const resourceKey = this.resourceName?.toLowerCase() ?? 'unknown';
        const extensionKey = this.extensionName?.toLowerCase() ?? 'unknown';

        const action = this.intl.t(`common.${actionKey}`);
        const resource = this.intl.t(`resource.${resourceKey}`);
        const extension = this.intl.t(`resource.${extensionKey}`);


        let actionPreposition = 'a';
        let resourcePreposition = getPermissionAction(this.name) === '*' && resource ? 'con' : '';
        let extensionPreposition = 'en';
        

        return this.intl.t('permission.description', {
            action,
            actionPreposition,
            resource,
            resourcePreposition,
            extension,
            extensionPreposition
        });
    }

    @computed('name', 'intl.locale') get textoMostrar() {
        const parts = (this.name ?? '').split(' ');
        const actionKey = parts[1]?.toLowerCase() ?? 'unknown';
        const resourceKey = parts[2]?.toLowerCase() ?? 'unknown';
        const extensionKey = parts[0]?.toLowerCase() ?? 'unknown';

        const action = this.intl.exists(`common.${actionKey}`) ? this.intl.t(`common.${actionKey}`) : actionKey;
        const resource = this.intl.exists(`resource.${resourceKey}`) ? this.intl.t(`resource.${resourceKey}`) : resourceKey;
        const extension = this.intl.exists(`resource.${extensionKey}`) ? this.intl.t(`resource.${extensionKey}`) : extensionKey;

        console.log('textoMostrar ', action, actionKey, resource, resourceKey);

        return `${action} ${resource} ${extension}`.trim().toLowerCase();
    }

    @computed('updated_at') get updatedAgo() {
        return formatDistanceToNow(this.updated_at);
    }

    @computed('updated_at') get updatedAt() {
        return format(this.updated_at, 'PPP');
    }

    @computed('created_at') get createdAgo() {
        return formatDistanceToNow(this.created_at);
    }

    @computed('created_at') get createdAt() {
        return format(this.created_at, 'yyyy-MM-dd HH:mm');
    }
}
