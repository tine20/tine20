/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Date.msSECOND = 1000;
Date.msMINUTE = 60 * Date.msSECOND;
Date.msHOUR   = 60 * Date.msMINUTE;
Date.msDAY    = 24 * Date.msHOUR;
Date.msWEEK   =  7 * Date.msDAY;

Ext.ns('Tine.Calendar');

/**
 * @class Tine.Calendar.CalendarPanel
 * @namespace Tine.Calendar
 * @extends Ext.Panel
 * Calendar Panel, pooling together store, and view <br/>
 * @author Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.CalendarPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Tine.Calendar.someView} view
     */
    view: null,
    /**
     * @cfg {Ext.data.Store} store
     */
    store: null,
    
    /**
     * @cfg {Number} autoRefreshInterval (seconds)
     */
    autoRefreshInterval: 300,
    
    /**
     * @property autoRefreshTask
     * @type Ext.util.DelayedTask
     */
    autoRefreshTask: null,
    
    /**
     * @cfg {Bool} border
     */
    border: false,
    /**
     * @cfg {String} loadMaskText
     * _('Loading events, please wait...')
     */
    loadMaskText: 'Loading events, please wait...',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.Calendar.CalendarPanel.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.loadMaskText = this.app.i18n._hidden(this.loadMaskText);
        
        this.selModel = this.selModel || new Tine.Calendar.EventSelectionModel();
        
        this.autoScroll = false;
        this.autoWidth = false;
        
        /**
         * @event click
         * fired if an event got clicked
         * @param {Tine.Calendar.Model.Event} event
         * @param {Ext.EventObject} e
         */
        /**
         * @event contextmenu
         * fired if an event got contextmenu 
         * @param {Ext.EventObject} e
         */
        /**
         * @event dblclick
         * fired if an event got dblclicked
         * @param {Tine.Calendar.Model.Event} event
         * @param {Ext.EventObject} e
         */
        /**
         * @event changeView
         * fired if user wants to change view
         * @param {String} requested view name
         * @param {mixed} start param of requested view
         */
        /**
         * @event changePeriod
         * fired when period changed
         * @param {Object} period
         */
        this.relayEvents(this.view, ['changeView', 'changePeriod', 'click', 'dblclick', 'contextmenu']);
        
        this.store.on('beforeload', this.onBeforeLoad, this);
        this.store.on('load', this.onLoad, this);
        this.store.on('loadexception', this.onLoadException, this);
        
        // init autoRefresh
        this.autoRefreshTask = new Ext.util.DelayedTask(this.store.load, this.store, [{
            refresh: true,
            autoRefresh: true
        }]);
    },
    
    /**
     * Returns selection model
     * 
     * @return {Tine.Calendar.EventSelectionModel}
     */
    getSelectionModel: function() {
        return this.selModel;
    },
    
    /**
     * Returns data store
     * 
     * @return {Ext.data.Store}
     */
    getStore: function() {
        return this.store;
    },
    
    /**
     * Retruns calendar View
     * 
     * @return {Tine.Calendar.View}
     */
    getView: function() {
        return this.view;
    },
    
    onAddEvent: function(event, checkBusyConficts) {
        this.setLoading(true);
        
        // remove temporary id
        if (event.get('id').match(/new/)) {
            event.set('id', '');
        }
        
        if (event.isRecurBase()) {
            this.loadMask.show();
        }
        
        Tine.Calendar.backend.saveRecord(event, {
            scope: this,
            success: function(createdEvent) {
                if (createdEvent.isRecurBase()) {
                    this.store.load({refresh: true});
                } else {
                    this.store.remove(event);
                    this.store.add(createdEvent);
                    this.setLoading(false);
                    if (this.view && this.view.calPanel && this.view.rendered) {
                        this.view.getSelectionModel().select(createdEvent);
                    }
                }
            },
            failure: this.onProxyFail.createDelegate(this, [event], true)
        }, {
            checkBusyConficts: checkBusyConficts === false ? 0 : 1
        });
    },
    
    onBeforeLoad: function(store, options) {
        if (! options.refresh) {
            if (this.rendered) {
                // defer to have the loadMask centered in case of rendering actions
                this.loadMask.show.defer(50, this.loadMask);
            }
            this.store.each(this.view.removeEvent, this.view);
        }
        
        options.params = options.params || {};
        
        var filter = options.params.filter ? options.params.filter : [];
        
        filter.push({field: 'period', operator: 'within', value: this.getView().getPeriod() });
    },
    
    onLoad: function() {
        if (this.rendered) {
            this.loadMask.hide();
        }
        
        // reset autoRefresh
        if (window.isMainWindow && this.autoRefreshInterval) {
            this.autoRefreshTask.delay(this.autoRefreshInterval * 1000);
        }
    },
    
    /**
     * on store load exception
     * 
     * @param {Tine.Tinebase.data.RecordProxy} proxy
     * @param {String} type
     * @param {Object} error
     * @param {Object} options
     */
    onLoadException: function(proxy, type, error, options) {
        
        // reset autoRefresh
        if (window.isMainWindow && this.autoRefreshInterval) {
            this.autoRefreshTask.delay(this.autoRefreshInterval * 5000);
        }
        
        this.setLoading(false);
        this.loadMask.hide();
        
        if (! options.autoRefresh) {
            this.onProxyFail(error);
        }
    },
    
    onProxyFail: function(error, event) {
        this.setLoading(false);
        this.loadMask.hide();
        
        if (error.code == 901) {
            
            // resort fbInfo to combine all events of a attender
            var busyAttendee = [];
            var conflictEvents = {};
            var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(event.get('attendee'));
            Ext.each(error.freebusyinfo, function(fbinfo) {
                attendeeStore.each(function(a) {
                    if (a.get('user_type') == fbinfo.user_type && a.getUserId() == fbinfo.user_id) {
                        if (busyAttendee.indexOf(a) < 0) {
                            busyAttendee.push(a);
                            conflictEvents[a.id] = [];
                        }
                        conflictEvents[a.id].push(fbinfo);
                    }
                });
            }, this);
            
            // generate html for each busy attender
            var busyAttendeeHTML = '';
            Ext.each(busyAttendee, function(busyAttender) {
                // TODO refactore name handling of attendee
                //      -> attender model needs knowlege of how to get names!
                //var attenderName = a.getName();
                var attenderName = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Calendar.AttendeeGridPanel.prototype, busyAttender.get('user_id'), false, busyAttender);
                busyAttendeeHTML += '<div class="cal-conflict-attendername">' + attenderName + '</div>';
                
                var eventInfos = [];
                Ext.each(conflictEvents[busyAttender.id], function(fbInfo) {
                    var format = 'H:i';
                    var dateFormat = Ext.form.DateField.prototype.format;
                    if (event.get('dtstart').format(dateFormat) != event.get('dtend').format(dateFormat) ||
                        Date.parseDate(fbInfo.dtstart, Date.patterns.ISO8601Long).format(dateFormat) != Date.parseDate(fbInfo.dtend, Date.patterns.ISO8601Long).format(dateFormat))
                    {
                        format = dateFormat + ' ' + format;
                    }
                    
                    var eventInfo = Date.parseDate(fbInfo.dtstart, Date.patterns.ISO8601Long).format(format) + ' - ' + Date.parseDate(fbInfo.dtend, Date.patterns.ISO8601Long).format(format);
                    if (fbInfo.event && fbInfo.event.summary) {
                        eventInfo += ' : ' + fbInfo.event.summary;
                    }
                    eventInfos.push(eventInfo);
                }, this);
                busyAttendeeHTML += '<div class="cal-conflict-eventinfos">' + eventInfos.join(', <br />') + '</div>';
                
            });
            
            this.conflictConfirmWin = Tine.widgets.dialog.MultiOptionsDialog.openWindow({
                modal: true,
                allowCancel: false,
                height: 180 + 15*error.freebusyinfo.length,
                title: this.app.i18n._('Scheduling Conflict'),
                questionText: '<div class = "cal-conflict-heading">' +
                                   this.app.i18n._('The following attendee are busy at the requested time:') + 
                               '</div>' +
                               busyAttendeeHTML,
                options: [
                    {text: this.app.i18n._('Ignore Conflict'), name: 'ignore', checked: true},
                    {text: this.app.i18n._('Edit Event'), name: 'edit'},
                    {text: this.app.i18n._('Cancel this action'), name: 'cancel'}
                ],
                scope: this,
                handler: function(option) {
                    switch (option) {
                        case 'ignore':
                            this.onAddEvent(event, false);
                            this.conflictConfirmWin.close();
                            break;
                        
                        case 'edit':
                            this.view.getSelectionModel().select(event);
                            // mark event as not dirty to allow edit dlg
                            event.dirty = false;
                            this.view.fireEvent('dblclick', this.view, event);
                            this.conflictConfirmWin.close();
                            break;
                            
                        case 'cancel':
                        default:
                            this.conflictConfirmWin.close();
                            this.loadMask.show();
                            this.store.load({refresh: true});
                            break;
                    }
                }
            });
            
        } else {
            Tine.Tinebase.ExceptionHandler.handleRequestException(error);
        }
    },
    
    onUpdateEvent: function(event) {
        this.setLoading(true);
        
        if (event.isRecurBase()) {
            this.loadMask.show();
        }
        
        if (event.isRecurInstance() || (event.isRecurBase() && ! event.get('rrule').newrule)) {
            this.deleteMethodWin = Tine.widgets.dialog.MultiOptionsDialog.openWindow({
                title: this.app.i18n._('Update Event'),
                height: 170,
                scope: this,
                options: [
                    {text: this.app.i18n._('Update this event only'), name: 'this'},
                    {text: this.app.i18n._('Update this and all future events'), name: (event.isRecurBase() && ! event.get('rrule').newrule) ? 'series' : 'future'},
                    {text: this.app.i18n._('Update whole series'), name: 'series'},
                    {text: this.app.i18n._('Update nothing'), name: 'cancel'}
                    
                ],
                handler: function(option) {
                    switch (option) {
                        case 'series':
                            this.loadMask.show();
                            
                            var options = {
                                scope: this,
                                success: function() {
                                    this.store.load({refresh: true});
                                },
                                failure: this.onProxyFail.createDelegate(this, [event], true)
                            };
                            
                            Tine.Calendar.backend.updateRecurSeries(event, options);
                            break;
                            
                        case 'this':
                        case 'future':
                            var options = {
                                scope: this,
                                success: function(updatedEvent) {
                                    if (option === 'this') {
                                        event =  this.store.indexOf(event) != -1 ? event : this.store.getById(event.id);
                            
                                        this.store.remove(event);
                                        this.store.add(updatedEvent);
                                        this.setLoading(false);
                                        this.view.getSelectionModel().select(updatedEvent);
                                    } else {
                                        this.store.load({refresh: true});
                                    }
                                },
                                failure: this.onProxyFail.createDelegate(this, [event], true)
                            };
                            
                            Tine.Calendar.backend.createRecurException(event, false, option == 'future', options);
                                
                        default:
                            this.loadMask.show();
                            this.store.load({refresh: true});
                            break;
                    }
                }
            });
        } else {
            this.onUpdateEventAction(event);
        }
    },
    
    onUpdateEventAction: function(event) {
        Tine.Calendar.backend.saveRecord(event, {
            scope: this,
            success: function(updatedEvent) {
                //console.log('Backend returned updated event -> replace event in view');
                if (updatedEvent.isRecurBase()) {
                    this.store.load({refresh: true});
                } else {
                    event =  this.store.indexOf(event) != -1 ? event : this.store.getById(event.id);
                    
                    this.store.remove(event);
                    this.store.add(updatedEvent);
                    this.setLoading(false);
                    this.view.getSelectionModel().select(updatedEvent);
                }
            },
            failure: this.onProxyFail.createDelegate(this, [event], true)
        }, {
            checkBusyConficts: 1
        });
    },
    
    setLoading: function(bool) {
        var tbar = this.getTopToolbar();
        if (tbar && tbar.loading) {
            tbar.loading[bool ? 'disable' : 'enable']();
        }
    },
    
    /*
    onRemoveEvent: function(store, event, index) {
        console.log(event);
        console.log('A existing event has been deleted -> call backend delete'); 
    },
    */
    
    /**
     * @private
     */
    onRender: function(ct, position) {
        Tine.Calendar.CalendarPanel.superclass.onRender.apply(this, arguments);
        
        this.loadMask = new Ext.LoadMask(this.body, {msg: this.loadMaskText});
        
        var c = this.body;
        this.el.addClass('cal-panel');
        this.view.init(this);
        
        // quick add/update actions
        this.view.on('addEvent', this.onAddEvent, this);
        this.view.on('updateEvent', this.onUpdateEvent, this);
        
        this.view.on("click", this.onClick, this);
        this.view.on("dblclick", this.onDblClick, this);
        this.view.on("contextmenu", this.onContextMenu, this);
        
        c.on("keydown", this.onKeyDown, this);
        //this.relayEvents(c, ["keypress"]);
        
        this.view.render();
    },
    
    /**
     * @private
     */
    afterRender : function(){
        Tine.Calendar.CalendarPanel.superclass.afterRender.call(this);
        
        this.view.layout();
        this.view.afterRender();
        
        this.viewReady = true;
    },
    
    /**
     * @private
     */
    onResize: function(ct, position) {
        Tine.Calendar.CalendarPanel.superclass.onResize.apply(this, arguments);
        if(this.viewReady){
            this.view.layout();
        }
    },
    
    /**
     * @private
     */
    processEvent : function(name, event){
        //console.log('Tine.Calendar.CalendarPanel::processEvent "' + name + '" on envent: ' + event.id );
    },
    
    /**
     * @private
     */
    onClick : function(event, e){
        this.processEvent("click", event);
    },

    /**
     * @private
     */
    onContextMenu : function(event, e){
        this.processEvent("contextmenu", event);
    },

    /**
     * @private
     */
    onDblClick : function(event, e){
        this.processEvent("dblclick", event);
    },
    
    /**
     * @private
     */
    onKeyDown : function(e){
        this.fireEvent("keydown", e);
    }
    
});
