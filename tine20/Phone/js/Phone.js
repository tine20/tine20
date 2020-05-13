/*
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         refactor this!
 */
 
Ext.namespace('Tine.Phone');

require('../../Voipmanager/js/Models');

/**************************** dialer form / function *******************************/

Tine.Phone.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * holds the users' phones
     * @type {Ext.data.JsonStore}
     */
    userPhonesStore: null,
    
    init: function() {
        // create store (get from initial data)
        this.userPhonesStore = new Ext.data.JsonStore({
            fields: Tine.Voipmanager.Model.SnomPhone,
            // initial data from http request
            data: Tine.Phone.registry.get('Phones'),
            autoLoad: true,
            id: 'id'
        });
        new Tine.Phone.AddressbookGridPanelHook({app: this});
    }
});


/**
 * dial function
 * - opens the dialer window if multiple phones/lines are available
 * - directly calls dial json function if number is set and just 1 phone and line are available
 * 
 * @param string phone number to dial
 * 
 * @todo use window factory later
 * @todo what todo if no lines are available?
 */
Tine.Phone.dialPhoneNumber = function(number) {
    var phonesStore = Tine.Tinebase.appMgr.get('Phone').userPhonesStore;
    var lines = (phonesStore.getAt(0)) ? phonesStore.getAt(0).data.lines : [];
    
    // check if only one phone / one line exists and number is set
    if (phonesStore.getTotalCount() == 1 && lines.length == 1 && number) {
        // call Phone.dialNumber
        Ext.Ajax.request({
            url: 'index.php',
            params: {
                method: 'Phone.dialNumber',
                number: number,
                phoneId: phonesStore.getAt(0).id,
                lineId: lines[0].id 
            },
            success: function(_result, _request){
                // success
            },
            failure: function(result, request){
                // show error message?
            }
        });

    } else {

        // open dialer box (with phone and lines selection)
        var dialerPanel = new Tine.Phone.DialerPanel({
            number: (number) ? number : null
        });
        
        var dialer = Tine.WindowFactory.getWindow({
            title: 'Dial phone number',
            id: 'dialerWindow',
            modal: true,
            width: 400,
            height: 150,
            layout: 'fit',
            plain:true,
            bodyStyle:'padding:5px;',
            closeAction: 'close',
            items: [dialerPanel] 
        });
    }
};

/**
 * register special renderer for direction and destination
 */
Tine.widgets.grid.RendererManager.register('Phone', 'Call', 'direction', function(v) {
    var t = Tine.Tinebase.appMgr.get('Phone').i18n;
    switch(v) {
        case 'in':
            return '<img src="images/call-incoming.png" width="12" height="12" alt="contact" ext:qtip="' + Ext.util.Format.htmlEncode(t._('Incoming call')) + '"/>';
            break;
            
        case 'out':
            return '<img src="images/call-outgoing.png" width="12" height="12" alt="contact" ext:qtip="' + Ext.util.Format.htmlEncode(t._('Outgoing call')) + '"/>';
            break;
    }
});

Tine.widgets.grid.RendererManager.register('Phone', 'Call', 'destination', function(v) {
    if (v.toString().toLowerCase() == 'unknown') {
        var t = Tine.Tinebase.appMgr.get('Phone').i18n;
        return t._('unknown number');
    }
    
    return Ext.util.Format.htmlEncode(v);
});

Tine.widgets.grid.RendererManager.register('Phone', 'Call', 'line_id', function(v) {
    return (v && v.hasOwnProperty('linenumber')) ? v.linenumber : '';
});

Tine.widgets.grid.RendererManager.register('Phone', 'Call', 'contact_id', function(v) {
    return (v && v.hasOwnProperty('n_fn')) ? v.n_fn : '';
});

/***************************** utils ****************************************/

Ext.namespace('Tine.Phone.utils');

/**
 * checks if given argument is syntactically a callable/valid number
 * 
 * @todo: synchrosize this with the asterisk rules
 */
Tine.Phone.utils.isCallable = function(number) {
    return ! number.toString().replace(/^\+|[ \-\/]/g, '').match(/[^0-9]/);
};
