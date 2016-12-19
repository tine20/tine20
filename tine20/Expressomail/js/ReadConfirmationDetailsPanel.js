
Ext.ns('Tine.Expressomail');

Tine.Expressomail.ReadConfirmationRecord = Tine.Tinebase.data.Record.create([
   {name: 'id', type: 'int'},
   {name: 'to'},
   {name: 'subject'},
   {name: 'from_name'},
   {name: 'from_email'},
   {name: 'received', type: 'date', dateFormat: Date.patterns.ISO8601Long},
   {name: 'read_time', type: 'date', dateFormat: Date.patterns.ISO8601Long}
   
], {
    appName: 'Expressomail',
    modelName: 'ReadConfirmation',
    idProperty: 'id'
});

Tine.Expressomail.ReadConfirmationRecordProxy  = new Tine.Tinebase.data.RecordProxy({
    appName: 'Expressomail',
    modelName: 'ReadConfirmation',
    recordClass: Tine.Expressomail.ReadConfirmationRecord
});



/**
 * read confirmation grid panel
 * 
 * @namespace   Tine.Expressomail
 * @class       Tine.Expressomail.ReadConfirmationDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 * 
 * <p>Tinebase Expressomail ReadConfirmationDetailsPanel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * 
 * Create a new Tine.Expressomail.ReadConfirmationDetailsPanel
 * 
 */

Tine.Expressomail.ReadConfirmationDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {

    
    /**
     * @cfg {Object} preparedPart
     * server prepared text/webconference Invite part 
     */
    preparedPart: null,
    
    
    /**
     * @property statusActions
     * @type Array
     */
    statusActions:[],
    
    /**
     * init this component
     */
    initComponent: function() {
        
        this.app = Tine.Tinebase.appMgr.get('Expressomail');
        this.record = Tine.Expressomail.ReadConfirmationRecordProxy.recordReader({
                        responseText: Ext.util.JSON.encode(this.preparedPart.preparedData)
                    });
        
        this.initToolbar();

        this.on('afterrender', this.showEmail, this);

        Tine.Expressomail.ReadConfirmationDetailsPanel.superclass.initComponent.call(this);
    },
    
    /**
     * Invite action toolbar
     */
    initToolbar: function() {
        var singleRecordPanel = this.getSingleRecordPanel();
        
        this.actions = [];
        this.statusActions = [];
        
        
        this.statusActions.push (this.acceptAction);
        this.actions = this.actions.concat(this.statusActions);
        
        // add more actions here (no spam / apply / crush / send event / ...)
        
        this.clause = new Ext.Toolbar.TextItem({
            text: ''
        });
        this.tbar = new Ext.Toolbar({
            items: [{
                xtype: 'tbitem',
                cls: 'ExpressomailIconCls',
                width: 16,
                height: 16,
                style: 'margin: 3px 5px 2px 5px;'
            },
            this.clause,
            '->'
            ]//.concat(this.actions)
        });
    },
    
    /**
     * show/layout Email panel
     */
    showEmail: function() {
        
        
        var singleRecordPanel = this.getSingleRecordPanel();
        
        this.clause.setText(this.app.i18n._('Reading Confirmation:') + ' ' + this.record.get('subject'));
        
        this.getLoadMask().hide();
        singleRecordPanel.setVisible(true);
        singleRecordPanel.setHeight(150);
        
        singleRecordPanel.loadRecord(this.record);
    },
    
  
    createOffset: function() {
        var date = new Date();
        var sign = (date.getTimezoneOffset() > 0) ? "-" : "+";
        var offset = Math.abs(date.getTimezoneOffset());
        var hours = Math.floor(offset / 60);
            hours = hours < 10 ? '0' + hours : hours;
        var minutes = offset % 60;
            minutes = minutes < 10 ? '0' + minutes : minutes;
        return '(GMT'+sign + hours + ":" + minutes + ')';
    },
    
    /**
     * renders datetime in user browser timezone
     * 
     * @param {Date} dt
     * @return {String}
     */
    datetimeRenderer: function(dt) {
        var offset = new Date().getTimezoneOffset();
        dt.setTime(dt.getTime() - (offset * 60 * 1000));
  
        return Tine.Tinebase.common.dateRenderer(dt) + ' ' + dt.format('H:i:s') + ' ' + this.createOffset();
    },
    /**
     * main email details panel
     * 
     * @return {Ext.ux.display.DisplayPanel}
     */
    getSingleRecordPanel: function() {
        if (! this.singleRecordPanel) {
	    var _this = this;
            this.singleRecordPanel = new Ext.ux.display.DisplayPanel ({

                layout: 'fit',
                border: false,
                items: [{
                    layout: 'vbox',
                    border: false,
                    layoutConfig: {
                        align:'stretch'
                    },
                    items: [{
                        layout: 'hbox',
                        flex: 0,
                        height: 16,
                        border: false,
                        style: 'padding-left: 5px; padding-right: 5px',
                        layoutConfig: {
                            align:'stretch'
                        },
                        items: []
                    }, {
                        layout: 'hbox',
                        flex: 1,
                        border: false,
                        layoutConfig: {
                            padding:'5',
                            align:'stretch'
                        },
                        defaults:{
                            margins:'0 5 0 0'
                        },
                        items: [
                            {
                                flex: 2,
                                layout: 'ux.display',
                                labelWidth: 100,
                                layoutConfig: {
                                    background: 'solid'
                                },
                                items: [
                                    {
                                        xtype: 'label',
                                        html: this.app.i18n._('Your message:') + ' ' + this.record.get('subject')
                                    },
                                    {
                                        xtype: 'label',
                                        html:'<br/>' + this.app.i18n._('Received on') + ' ' + this.datetimeRenderer(this.record.get('received'))
                                    },
                                    {
                                        xtype: 'label',
                                        html: '<br/>' + this.app.i18n._('Was read by:') + ' ' + this.record.get('from_name')  +  ' < ' + this.record.get('from_email') +' > ' + this.app.i18n._('on') + ' ' + this.datetimeRenderer(this.record.get('read_time'))
                                    }
                                ]
                            }
                            ]
                    }]
                }]
            });
        }
        
        return this.singleRecordPanel;
    }
});
