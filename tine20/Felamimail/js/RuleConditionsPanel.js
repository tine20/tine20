/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.RuleConditionsPanel
 * @extends     Tine.widgets.grid.FilterToolbar
 * 
 * <p>Sieve Filter Conditions Panel</p>
 * <p>
 * TODO         add more filter models
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new RuleConditionsPanel
 */
Tine.Felamimail.RuleConditionsPanel = Ext.extend(Tine.widgets.grid.FilterToolbar, {
    defaultFilter: 'from',
    allowSaving: false,
    showSearchButton: false,
     
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        
        this.filterModels = [
            {label: this.app.i18n._('From'),     field: 'from',      operators: ['contains']},
            {label: this.app.i18n._('To'),       field: 'to',        operators: ['contains']},
            {label: this.app.i18n._('Subject'),  field: 'subject',   operators: ['contains']}
        ];
        
        this.supr().initComponent.call(this);
    },
    
    // unused fn
    onFiltertrigger: Ext.emptyFn
});
