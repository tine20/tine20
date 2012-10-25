/**
 * Tine 2.0
 * 
 * @package Sipgate
 * @license http://www.gnu.org/licenses/agpl.html AGPL3
 * @author Alexander Stintzing <alex@stintzing.net>
 * @copyright Copyright (c) 2011 Metaways Infosystems GmbH
 *            (http://www.metaways.de)
 * @version $Id: Sipgate.js 24 2011-05-02 02:47:52Z alex $
 * 
 */

Ext.ns('Tine.Sipgate');

// APPLICATION WIDE

Tine.Sipgate.Application = Ext.extend(Tine.Tinebase.Application, {  
    init: function() {
        new Tine.Sipgate.AddressbookGridPanelHook({app: this});
    }
});

Tine.Sipgate.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Connection',
    contentTypes: [
        {model: 'Connection',  requiredRight: null, singularContainerMode: true},
        {model: 'Line',  requiredRight: null, singularContainerMode: true},
        {model: 'Account',  requiredRight: 'manage', singularContainerMode: true}
    ]
});

// CONNECTION

Tine.Sipgate.ConnectionFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Sipgate.ConnectionFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Sipgate.ConnectionFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
});

// LINE

Tine.Sipgate.LineFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Sipgate.LineFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Sipgate.LineFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Sipgate_Model_LineFilter'}]
});

// LINE

Tine.Sipgate.AccountFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Sipgate.AccountFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Sipgate.AccountFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Sipgate_Model_AccountFilter'}]
});

// common

Tine.Sipgate.common = {
    
    renderE164In: function(value) {
        return Ext.decode(value).join(', ');
    }
};