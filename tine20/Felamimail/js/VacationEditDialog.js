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
 * @class       Tine.Felamimail.VacationEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Sieve Filter Dialog</p>
 * <p>This dialog is editing sieve filters (vacation and rules).</p>
 * <p>
 * TODO         add more fields
 * TODO         add rules ? or another edit dlg for rules?
 * </p>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @version     $Id$
 * 
 * @param       {Object} config
 * @constructor
 * Create a new VacationEditDialog
 */
 Tine.Felamimail.VacationEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @cfg {Tine.Felamimail.Model.Account}
     */
    account: null,
    
    /**
     * @private
     */
    windowNamePrefix: 'VacationEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Vacation,
    recordProxy: Tine.Felamimail.vacationBackend,
    loadRecord: true,
    tbarItems: [],
    evalGrants: false,
    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * 
     * @private
     */
    updateToolbars: function() {

    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        this.getForm().loadRecord(this.record);
        
        //Tine.log.debug(this.record);
        //Tine.log.debug(this.account);
        
        var title = String.format(this.app.i18n._('Vacation Message for {0}'), this.account.get('name'));
        this.window.setTitle(title);
        
        this.loadMask.hide();
    },
        
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     * 
     * TODO get css definitions from external stylesheet?
     */
    getFormItems: function() {
        
        this.reasonEditor = new Ext.form.HtmlEditor({
            fieldLabel: this.app.i18n._('Reason'),
            name: 'reason',
            allowBlank: true,
            height: 220,
            /*
            getDocMarkup: function(){
                var markup = '<span id="felamimail\-body\-signature">'
                    + '</span>';
                return markup;
            },
            */
            plugins: [
                new Ext.ux.form.HtmlEditor.RemoveFormat()
            ]
        });
        
        return {
            autoScroll: true,
            border: false,
            frame: true,
            xtype: 'columnform',
            formDefaults: {
                xtype:'textfield',
                anchor: '100%',
                labelSeparator: '',
                columnWidth: 1
            },
            items: [[this.reasonEditor]]
        };
    }
});

/**
 * Felamimail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.VacationEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 640,
        height: 480,
        name: Tine.Felamimail.VacationEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.VacationEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
