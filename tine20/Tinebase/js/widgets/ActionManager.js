/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine', 'Tine.widgets');

/**
 * get a fresh set of actions
 *
 * NOTE: An Action is a piece of reusable functionality that can be abstracted
 * out of any particular component so that it can be usefully shared among
 * multiple components. Actions let you share handlers, configuration options
 * and UI updates across any components that support the Action interface
 * (primarily Ext.Toolbar, Ext.Button and Ext.menu.Menu components).
 *
 * NOTE: Using the same action multiple times requires a shared selection model
 *       a) action toolbar is valid for grid selections (and maybe tree selections
 *          and state). So the actions can't be shared with context menus!
 *       b) context menus for grid/tree have different selection mechanisms
 *          i) selected - when the item the context menu was issued on is
 *             part of the current tree/grid selection the context actions
 *             are updated with the current selection and have to be applied
 *             on all selected items
 *         ii) context - when the item the context menu was issued on is
 *             not part of the current grid/tree selection the context actions
 *             are updated with the single underlaying item and have to be applied
 *             to the underlaying item only.
 *          as only one context menu is visible at a time, it's ok to share
 *          actions in context menus, as they are updated with the selection
 *          before the menu is shown.
 *
 *
 * NOTE: Each action should have an action updater - even to apply initial sate.
 *       The action updater runs every time the selection/scope for the action changes.
 *
 * IMPORTANT: it's in the responsibility of the component which created the action
 *       to update it!
 *
 * NOTE: consumers don't directly listen to events fired by the event handlers
 *       all communication is done via the message bus or uploadManager
 *       @TODO rethink: do we need events/callbacks for e.g. for window close?
 *       @TODO do the consumers need something like 'opener'?
 */
Tine.widgets.ActionManager = function(config) {
    Ext.apply(this, config);
};

Tine.widgets.ActionManager.prototype = {

    /**
     * @cfg {Object} actionConfigs
     */
    actionConfigs: null,

    getConfig: function(type) {
        var  _ = window.lodash,
            name = _.upperFirst(type);

        return _.get(this.actionConfigs, name);
    },

    /**
     * check if actionConfigs for type is available
     *
     * @param {String}
     * @return {Bool}
     */
    has: function(type) {
        return !!this.getConfig(type);
    },

    /**
     * get new action by actionType
     *
     * @param {String} type
     * @param {Object} config
     * @returns {Ext.Action}
     */
    get: function(type, config) {
        var  _ = window.lodash;

        if (_.isArray(type)) {
            return _.map(type, _.bind(this.get, this, _, config));
        }

        config = config || {};
        // NOTE: we copy shared config into a new object to avoid "static" properties
        //       in the instances
        Ext.applyIf(config, this.getConfig(type));

        if (_.isString(config.app)) {
            config.app = Tine.Tinebase.appMgr.get(config.app);
        }

        if (!config.translationObject && config.app) {
            config.translationObject = config.app.i18n;
        }

        if (config.translationObject && config.text) {
            config.text = config.translationObject._hidden(config.text);
        }

        // NOTE: an Ext.Action can't be extended :-(
        var action = new Ext.Action(config);
        action.initialConfig.scope = action;
        if (_.isFunction(action.initialConfig.init)) {
            action.initialConfig.init.call(action.initialConfig);
        }

        return action;
    }
};