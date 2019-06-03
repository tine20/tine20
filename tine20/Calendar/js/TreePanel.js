/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.FilterPanel
 * @extends     Tine.widgets.persistentfilter.PickerPanel
 * 
 * <p>Calendar Favorites Panel</p>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Calendar.FilterPanel
 */
Tine.Calendar.FilterPanel = Ext.extend(Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Calendar_Model_EventFilter'}],
    
    initComponent : function() {
        // TODO this is set to null somewhere in the Calendar, find a better place to fix this
        this.contentType = 'Event';

        Tine.Calendar.FilterPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns filter toolbar of mainscreen center panel of app this picker panel belongs to
     */
    getFilterToolbar: function() {
        return this.app.getMainScreen().getCenterPanel().filterToolbar;
    },
    
    storeOnBeforeload: function(store, options) {
        store.un('beforeload', this.storeOnBeforeload, this);
        
        options.params.filter = options.persistentFilter.get('filters');
        
        // take a full clone to not taint the original filter
        options.params.filter = Ext.decode(Ext.encode(options.params.filter));
        
        var cp = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getCenterPanel();
        var period = cp.getCalendarPanel(cp.activeView).getView().getPeriod();
        
        // remove all existing period filters
        Ext.each(options.params.filter, function(filter) {
            if (filter.field === 'period') {
                options.params.filter.remove(filter);
                return false;
            }
        }, this);
        
        options.params.filter.push({field: 'period', operator: 'within', value: period});
    }
});

/**
 * @namespace Tine.Calendar
 * @class     Tine.Calendar.CalendarSelectTreePanel
 * @extends   Tine.widgets.container.TreePanel
 * 
 * Main Calendar Select Panel
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Calendar.TreePanel = Ext.extend(Tine.widgets.container.TreePanel, {

    recordClass: Tine.Calendar.Model.Event,
    ddGroup: 'cal-event',
    filterMode: 'filterToolbar',
    useContainerColor: true,
    useProperties: true,
    
    initComponent: function() {
        var _ = window.lodash,
            me = this;

        //@TODO improve detection or pipe as config
        this.isMainScreenFilterTree = this.hasContextMenu;

        // only apply filter plugin when used as mainscreen leftpanel
        if (this.isMainScreenFilterTree) {
            this.filterPlugin = new Tine.widgets.tree.FilterPlugin({
                treePanel: this,
                /**
                 * overwritten to deal with calendars special filter approach
                 *
                 * @return {Ext.Panel}
                 */
                getGridPanel: function () {
                    return Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getCenterPanel();
                }
            });
        }
        
        this.on('beforeclick', this.onBeforeClick, this);
        this.on('containercolorset', function() {
            Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getCenterPanel().refresh(true);
        });
        
        this.supr().initComponent.call(this);

        // remove resource calendars user has appropriate grants for
        this.loader.processResponse = _.wrap(this.loader.processResponse, function(orig, response, node, callback, scope) {
            var o = response.responseData || Ext.decode(response.responseText);
            response.responseData = o.hasOwnProperty('totalcount') ? o.results : o;

                response.responseData = _.reduce(response.responseData, function (newResponse, nodeData) {
                var grantsModelName = _.get(nodeData, 'xprops.Tinebase.Container.GrantsModel', 'Tinebase_Model_Grants'),
                    accountGrants = _.get(nodeData, 'account_grants', {}),
                    hasRequiredGrants = grantsModelName != 'Calendar_Model_ResourceGrants' ? true : (
                        (
                            _.get(accountGrants, 'resourceInviteGrant', false) ||
                            _.get(accountGrants, 'resourceReadGrant', false)
                        ) && (
                            _.get(accountGrants, 'eventsFreebusyGrant', false) ||
                            _.get(accountGrants, 'eventsReadGrant', false)
                        )
                    );

                if (grantsModelName == 'Calendar_Model_ResourceGrants') {
                    // transform grants for event container selection
                    accountGrants.addGrant = accountGrants.eventsAddGrant;
                    accountGrants.editGrant = accountGrants.eventsEditGrant;
                    accountGrants.deleteGrant = accountGrants.eventsDeleteGrant;
                }

                return hasRequiredGrants ? newResponse.concat([nodeData]) : newResponse;
            }, []);

            return orig.apply(me.loader, _.drop(arguments));
        });
    },

    initContextMenu: function() {
        this.supr().initContextMenu.call(this);
        this.action_editResource = new Ext.Action({
            iconCls: 'cal-resource',
            hidden: true,
            text: this.app.i18n._('Edit Resource'),
            handler: function() {
                var resource = new Tine.Calendar.Model.Resource({id: this.action_editResource.resourceId}, this.action_editResource.resourceId);
                Tine.Calendar.ResourceEditDialog.openWindow({record: resource});
            },
            scope: this
        });

        this.contextMenuSingleContainer.add(this.action_editResource);
        this.contextMenuSingleContainerProperties.add(this.action_editResource);
    },

    onContextMenu: function(node, event) {
        var grants = lodash.get(node, 'attributes.container.account_grants'),
            xprops = lodash.get(node, 'attributes.container.xprops'),
            resourceId;

        if (Ext.isString(xprops)) {
            xprops = Ext.decode(xprops);
        }

        resourceId = lodash.get(xprops, 'Calendar.Resource.resource_id');

        if (resourceId) {
            this.action_editResource.setText(grants.resourceEditGrant ||
                Tine.Tinebase.common.hasRight('manage', 'Calendar', 'resources') ?
                    this.app.i18n._('Edit Resource') :
                    this.app.i18n._('View Resource')
            );
        }

        if (grants) {
            this.action_editResource.setHidden(! grants.resourceReadGrant || ! resourceId);
        }

        this.action_editResource.resourceId = resourceId;

        this.supr().onContextMenu.apply(this, arguments);
    },
    /**
     * dissalow loading of all and otherUsers node
     * 
     * @param {Ext.tree.TreeNode} node
     * @param {Ext.EventObject} e
     * @return {Boolean}
     */
    onBeforeClick: function(node, e) {
        if (! node.disabled && node.attributes.path && node.attributes.path.match(/^\/$|^\/personal$/)) {
            this.onClick(node, e);
            return false;
        }
    },
    
    /**
     * adopt attr
     * 
     * @param {Object} attr
     */
    onBeforeCreateNode: function(attr) {
        var xprops = lodash.get(attr, 'xprops'),
            resourceIcon,
            typeId;

        this.supr().onBeforeCreateNode.apply(this, arguments);
        
        if (attr.container) {
            attr.container.capabilites_private = true;
        }

        if (Ext.isString(xprops)) {
            xprops = Ext.decode(xprops);
        }

        typeId = lodash.get(xprops, 'Calendar.Resource.resource_type');

        resourceIcon = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'resourceTypes').getById(typeId);

        
        if (resourceIcon) {
            attr.icon = resourceIcon.get('icon');
            attr.cls = attr.cls || '';
            attr.cls += ' cal-calendartree-resource-icon';
        }
    },
    
    /**
     * called when events are droped on a calendar node
     * 
     * NOTE: atm. event panels only allow d&d for single events
     * 
     * @private
     * @param  {Ext.Event} dropEvent
     * @return {Boolean}
     */
    onBeforeNodeDrop: function(dropEvent) {
        var containerData = dropEvent.target.attributes,
            selection = dropEvent.data.selections,
            mainScreenPanel = Tine.Tinebase.appMgr.get('Calendar').getMainScreen().getCenterPanel(),
            abort = false;

        // @todo move this to dragOver
        if (! containerData.account_grants.addGrant) {
            abort = true;
        }
        
        Ext.each(selection, function(event) {
            if (Tine.Tinebase.container.pathIsMyPersonalContainer(event.get('container_id').path)) {
                // origin container will only be moved for personal events with their origin in
                // a personal container of the current user
                event.set('container_id', containerData.id);
                mainScreenPanel.onUpdateEvent(event);

                dropEvent.cancel = false;
                dropEvent.dropStatus = true;
                
            } else {
                // @todo move displaycal if curruser is attender
                abort = true;
            }
        }, this);
        
        if (abort) {
            return false;
        }
    },

    getSelectedContainer: function(requiredGrants, defaultContainer, onlySingle) {
        var prefs = this.app.getRegistry().get('preferences');

        if ('none' === prefs.get('defaultCalendarStrategy')) {
            var sm = this.getSelectionModel(),
                selection = typeof sm.getSelectedNodes == 'function' ? sm.getSelectedNodes() : [sm.getSelectedNode()];

            if (selection.length > 1 || selection.length === 0) {
                return null;
            }
        }

        return this.supr().getSelectedContainer.call(this, requiredGrants, defaultContainer, onlySingle);
    }
});
