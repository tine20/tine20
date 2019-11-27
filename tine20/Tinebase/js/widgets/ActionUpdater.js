/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
 Ext.ns('Tine', 'Tine.widgets');
 
 Tine.widgets.ActionUpdater = function(config) {
    config = config || {};
    var actions = config.actions || [];
    config.actions = [];
    
    Ext.apply(this, config);

    if (this.recordClass && Ext.isFunction(this.recordClass.getMeta)) {
        this.grantsPath = this.recordClass.getMeta('grantsPath');
    }
    this.addActions(actions);
 };

 Tine.widgets.ActionUpdater.prototype = {
    /**
     * @cfg {Array|Toolbar} actions
     * all actions to update
     */
    actions: null,
    
    /**
     * @cfg {Bool} evalGrants
     * should grants of a grant-aware records be evaluated (defaults to true)
     */
    evalGrants: true,

    recordClass: null,

    grantsPath: null,
    
    /**
     * add actions to update
     * @param {Array|Toolbar} actions
     */
    addActions: function(actions) {
        if (Ext.isArray(actions)) {
            for (var i=0; i<actions.length; i++) {
                this.addAction(actions[i]);
            }
        } else if (Ext.isFunction(actions.each)) {
            actions.each(this.addAction, this);
        } else if (Ext.isObject(actions)) {
            for (var action in actions) {
                this.addAction(actions[action]);
            }
        }
    },
    
    /**
     * add a single action to update
     * @param {Ext.Action} action
     */
    addAction: function(action) {
        // register action once only!
        if (this.actions.indexOf(action) >= 0) {
            return;
        }

        // if action has to initialConfig it's no Ext.Action!
        if (action && action.initialConfig) {
            
            // in some cases our actionUpdater config is not in the initial config
            // this happens for direct extensions of button class, like the notes button
            if (action.requiredGrant) {
                Ext.applyIf(action.initialConfig, {
                    requiredGrant: action.requiredGrant,
                    actionUpdater: action.actionUpdater,
                    allowMultiple: action.allowMultiple,
                    singularText: action.singularText,
                    pluralText: action.pluralText,
                    translationObject: action.translationObject,
                    selections: []
                });
            }
            
            this.actions.push(action);

            if (action.initialConfig.menu) {
                this.addActions(action.initialConfig.menu.items ?
                    action.initialConfig.menu.items :
                    action.initialConfig.menu
                );
            }
        }
    },
    
    /**
     * performs the actual update
     * @param {Array|SelectionModel} records
     * @param {Array|Tine.Tinebase.Model.Container} container
     */
    updateActions: function(records, container) {
        var isFilterSelect = false,
            selectionModel = null;

        if (typeof(records.getSelections) == 'function') {
            isFilterSelect = records.isFilterSelect;
            selectionModel = records;
            records = records.getSelections();
        } else if (typeof(records.beginEdit) == 'function') {
            records = [records];
        }
        
        var grants = this.getGrantsSum(records);
        
        this.each(function(action) {
            // action updater opt-in fn has precedence over generic action updater!
            var actionUpdater = action.actionUpdater || action.initialConfig.actionUpdater;
            if (typeof(actionUpdater) == 'function') {
                var scope = action.scope || action.initialConfig.scope || window;
                actionUpdater.call(scope, action, grants, records, isFilterSelect, container);
            } else {
                this.defaultUpdater(action, grants, records, isFilterSelect, container);
            }

            // reference selection into action
            action.initialConfig.selections = records;
            action.initialConfig.isFilterSelect = isFilterSelect;
            action.initialConfig.selectionModel = selectionModel;

        }, this);
        
    },
    
    /**
     * default action updater
     * 
     * - sets disabled status based on grants and required grant
     * - sets disabled status based on allowMutliple
     * - sets action text (signular/plural text)
     * 
     * @param {Ext.Action} action
     * @param {Object} grants
     * @param {Object} records
     * @param {Boolean} isFilterSelect
     */
    defaultUpdater: function(action, grants, records, isFilterSelect) {
        var nCondition = records.length != 0 && (records.length > 1 ? action.initialConfig.allowMultiple : true),
            mCondition = records && records.length > 1,
            grantCondition = (! this.evalGrants) || grants[action.initialConfig.requiredGrant];

        // @todo discuss if actions are only to be touched if requiredGrant is set
        if (action.initialConfig.requiredGrant && action.initialConfig.requiredGrant != 'addGrant') {
            action.setDisabled(! (grantCondition && nCondition));
        }
        
        // disable on filter selection if required
        if(action.disableOnFilterSelection && isFilterSelect) {
            Tine.log.debug("disable on filter selection");
            action.setDisabled(true);
            return;
        }
        
        if (nCondition) {
            if (mCondition) {
                if (action.initialConfig.requiredMultipleRight) {
                    var right = action.initialConfig.requiredMultipleRight.split('_');
                    if (right.length == 2) {
                        action.setDisabled(!Tine.Tinebase.common.hasRight(right[0], action.initialConfig.scope.app.name, right[1]));
                    } else {
                        Tine.log.debug('multiple edit right was not properly applied');
                    }
                }
                if(action.initialConfig.requiredMultipleGrant) {
                    var hasRight = true;
                    if(Ext.isArray(records)) {
                        Ext.each(records, function(record) {
                            if(record.get('container_id') && record.get('container_id').account_grants) {
                                hasRight = (hasRight && (record.get('container_id').account_grants[action.initialConfig.requiredMultipleGrant]));
                            } else {
                                return false;
                            }
                        }, this);
                        action.setDisabled(! hasRight);
                    }
                }
            }
            if (action.initialConfig.singularText && action.initialConfig.pluralText && action.initialConfig.translationObject) {
                var text = action.initialConfig.translationObject.n_(action.initialConfig.singularText, action.initialConfig.pluralText, records.length);
                action.setText(text);
            }
        }
    },
    
    /**
     * Calls the specified function for each action
     * 
     * @param {Function} fn The function to call. The action is passed as the first parameter.
     * Returning <tt>false</tt> aborts and exits the iteration.
     * @param {Object} scope (optional) The scope in which to call the function (defaults to the action).
     */
    each: function(fn, scope) {
        for (var i=0; i<this.actions.length; i++) {
            if (fn.call(scope||this.actions[i], this.actions[i]) === false) break;
        }
    },
    
    /**
     * calculats the grants sum of the given record(s)
     * 
     * @param  {Array}  records
     * @return {Object} grantName: sum
     */
    getGrantsSum: function(records) {

        var _ = window.lodash,
            defaultGrant = records.length == 0 ? false : true,
            grants = {
                addGrant:       defaultGrant,
                adminGrant:     defaultGrant,
                deleteGrant:    defaultGrant,
                editGrant:      defaultGrant,
                readGrant:      defaultGrant,
                exportGrant:    defaultGrant,
                syncGrant:      defaultGrant
            };
        
        if (! this.evalGrants) {
            return grants;
        }
        
        var recordGrants;
        for (var i=0; i<records.length; i++) {
            recordGrants = _.get(records[i], this.grantsPath, null);
            // NOTE: we skip grant update for records without grantsPath
            if (recordGrants === null) {
                continue;
            }

            for (var grant in grants) {
                grants[grant] = _.get(grants, grant, false) && _.get(recordGrants, grant, false);
            }

            // custom grants model
            for (grant in recordGrants) {
                grants[grant] = _.get(grants, grant, defaultGrant) && _.get(recordGrants, grant, false);
            }
        }
        // if calculated admin right is true, overwrite all grants with true
        if(grants.adminGrant) {
            for (var grant in grants) {
                grants[grant] = true;
            }
        }
        
        return grants;
    }
};
 
/**
 * sets text and disabled status of a set of actions according to the grants 
 * of a set of records
 * @legacy use ActionUpdater Obj. instead!
 * 
 * @param {Array|SelectionModel} records
 * @param {Array|Toolbar}        actions
 * @param {String}               containerField
 * @param {Bool}                 skipGrants evaluation
 */
Tine.widgets.actionUpdater = function(records, actions, containerField, skipGrants) {
    if (!containerField) {
        containerField = 'container_id';
    }

    if (typeof(records.getSelections) == 'function') {
        records = records.getSelections();
    } else if (typeof(records.beginEdit) == 'function') {
        records = [records];
    }
    
    // init grants
    var defaultGrant = records.length == 0 ? false : true;
    var grants = {
        addGrant:    defaultGrant,
        adminGrant:  defaultGrant,
        deleteGrant: defaultGrant,
        editGrant:   defaultGrant,
        exportGrant: defaultGrant,
        readGrant:   defaultGrant,
        syncGrant:   defaultGrant
    };
    
    // calculate sum of grants
    for (var i=0; i<records.length; i++) {
        var recordGrants = records[i].get(containerField) ? records[i].get(containerField).account_grants : {};
        for (var grant in grants) {
            grants[grant] = grants[grant] & recordGrants[grant];
        }
    }

    // if calculated admin right is true, overwrite all grants with true
    if(grants.adminGrant) {
        for (var grant in grants) {
            grants[grant] = true;
        }
    }

    /**
     * action iterator
     */
    var actionIterator = function(action) {
        var initialConfig = action.initialConfig;
        if (initialConfig) {
            
            // happens for direct extensions of button class, like the notes button
            if (action.requiredGrant) {
                initialConfig = {
                    requiredGrant: action.requiredGrant,
                    allowMultiple: action.allowMultiple,
                    singularText: action.singularText,
                    pluralText: action.pluralText,
                    translationObject: action.translationObject
                };
            }
            
            // NOTE: we don't handle add action for the moment!
            var requiredGrant = initialConfig.requiredGrant;
            if (requiredGrant && requiredGrant != 'addGrant') {
                var enable = skipGrants || grants[requiredGrant];
                if (records.length > 1 && ! initialConfig.allowMultiple) {
                    enable = false;
                }
                if (records.length == 0) {
                    enable = false;
                }
                
                action.setDisabled(!enable);
                if (initialConfig.singularText && initialConfig.pluralText && initialConfig.translationObject) {
                    var text = initialConfig.translationObject.n_(initialConfig.singularText, initialConfig.pluralText, records.length);
                    action.setText(text);
                }
            }
        }
    };
    
    /**
     * call action iterator
     */
    switch (typeof(actions)) {
        case 'object':
            if (typeof(actions.each) == 'function') {
                actions.each(actionIterator, this);
            } else {
                for (var action in actions) {
                    actionIterator(actions[action]);
                }
            }
        break;
        case 'array':
            for (var i=0; i<actions.length; i++) {
                actionIterator(actions[i]);
            }
        break;
    }
    
};
