/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.CoreData');


/**
 * core data grid/edit dialog manager
 *
 *  @singleton
 */
Tine.CoreData.Manager = function() {
    var grids = {},
        editDialogs = {},
        toolbars = {};

    return {

        /**
         * create grid for given core data id
         *
         * @param configRecord
         * @param options
         * @returns Ext.form.Field|null
         */
        getGrid: function (id, options) {
            if (grids.hasOwnProperty(id)) {
                constr = grids[id].constr;
                if (! options) {
                    options = grids[id].options;
                }
            } else {
                Tine.log.warn('Id not registered in CoreData manager: ' + id);
            }

            return Ext.isFunction(constr) ? new constr(options) : null;
        },

        /**
         * create grid for given core data id
         *
         * @param configRecord
         * @param options
         * @returns Ext.form.Field|null
         */
        getEditDialog: function (id, options) {
            if (editDialogs.hasOwnProperty(id)) {
                constr = editDialogs[type];
            }
            return Ext.isFunction(constr) ? new constr(options) : null;
        },

        /**
         * create grid for given core data id
         *
         * @param configRecord
         * @param options
         * @returns Ext.form.Field|null
         *
         * TODO is this needed?
         */
        getToolbar: function (id, options) {
            if (toolbars.hasOwnProperty(id)) {
                constr = toolbars[type];
                if (! options) {
                    options = toolbars[id].options;
                }
            } else {
                Tine.log.warn('Id not registered in CoreData manager: ' + id);
            }

            return Ext.isFunction(constr) ? new constr(options) : null;
        },

        /**
         * register grid for given core data
         *
         *  @static
         *  @param id string
         *  @param constructor Function
         *  @param options
         */
        registerGrid: function (id, constructor, options) {
            grids[id] = {
                constr: constructor,
                options: options
            };
        },

        /**
         * register toolbar for given core data
         *
         *  @static
         *  @param id string
         *  @param constructor Function
         *  @param options
         *
         *  TODO is this needed?
         */
        registerToolbar: function (id, constructor, options) {
            toolbars[id] = {
                constr: constructor,
                options: options
            };
        },

        /**
         * register edit dialog for given core data
         *
         *  @static
         *  @param id string
         *  @param constructor Function
         */
        registerEditDialog: function (id, constructor) {
            editDialogs[id] = constructor;
        }
    };
}();
