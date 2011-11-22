/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Calendar');

/**
 * display panel for MIME type text/calendar
 * 
 * NOTE: this panel is registered on Tine.Calendar::init
 * 
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.iMIPDetailsPanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 */
Tine.Calendar.iMIPDetailsPanel = Ext.extend(Tine.Calendar.EventDetailsPanel, {
    /**
     * @cfg {Object} preparedPart
     * server prepared text/calendar iMIP part 
     */
    preparedPart: null,
    
    /**
     * init this component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.initIMIPToolbar();
        
        this.on('afterrender', this.showIMIP, this);
            
        Tine.Calendar.iMIPDetailsPanel.superclass.initComponent.call(this);
    },
    
//    /**
//     * process email invitation
//     * 
//     * @param {String} status
//     */
//    processInvitation: function(status) {
//        Tine.log.debug('Setting event attender status: ' + status);
//        
//        var firstPreparedPart = this.record.get('preparedParts')[0];
//        if (firstPreparedPart.contentType !== 'text/calendar') {
//            return;
//        }
//        
//        var invitationEvent = Tine.Calendar.backend.recordReader({
//                responseText: Ext.util.JSON.encode(firstPreparedPart.preparedData.event)
//            }),
//            myAttenderRecord = invitationEvent.getMyAttenderRecord();
//        
//        myAttenderRecord.set('status', status);
//        Tine.Felamimail.setInvitationStatus(invitationEvent.data, myAttenderRecord.data, this.onInvitationStatusUpdate.createDelegate(this));
//    },
//    
    setResponseStatus: Ext.emptyFn,
    
    initIMIPToolbar: function() {
        var singleRecordPanel = this.getSingleRecordPanel();
        
        this.statusActions = [];
        
        Tine.Calendar.Model.Attender.getAttendeeStatusStore().each(function(status) {
            // NEEDS-ACTION is not appropriate in iMIP context
            if (status.id == 'NEEDS-ACTION') return;
            
            this.statusActions.push({
                text: status.get('status_name'),
                handler: this.setResponseStatus.createDelegate(this, [status.id]),
                iconCls: 'cal-response-action-' + status.id
            });
        }, this);
        
        this.iMIPclause = new Ext.Toolbar.TextItem({
            text: this.app.i18n._('You received an event invitation. Please select a response.')
        });
        this.tbar = {
            items: [{
                    xtype: 'tbitem',
                    cls: 'CalendarIconCls',
                    width: 16,
                    height: 16,
                    style: 'margin: 3px 5px 2px 5px;'
                },
                this.iMIPclause,
                '->'
            ].concat(this.statusActions)
        };
        
    },
    
    /**
     * show/layout iMIP panel
     */
    showIMIP: function() {
        
        var singleRecordPanel = this.getSingleRecordPanel(),
            invitationEvent = Tine.Calendar.backend.recordReader({
                responseText: Ext.util.JSON.encode(this.preparedPart.preparedData.event)
            }),
            myAttenderRecord = invitationEvent.getMyAttenderRecord(),
            myAttenderstatus = myAttenderRecord.get('status');
    
        Tine.log.debug('Tine.Calendar.iMIPDetailsPanel::showIMIP invitation status: ' + myAttenderstatus);
        
        if (myAttenderstatus !== 'NEEDS-ACTION') {
//            this.onInvitationStatusUpdate();
        }
        
        singleRecordPanel.setVisible(true);
        singleRecordPanel.setHeight(150);
        
        singleRecordPanel.loadRecord.defer(100, singleRecordPanel, [invitationEvent]);
    }
});