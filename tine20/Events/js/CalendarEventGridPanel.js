/*
 * Tine 2.0
 * 
 * @package     Events
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Events');

/**
 * Events grid panel
 * 
 * @namespace   Tine.Events
 * @class       Tine.Events.CalendarEventGridPanel
 * @extends     Tine.Calendar.GridView
 * 
 * <p>Calendar Events Grid Panel</p>
 * <p><pre>
 *     this is used in the EventEditDialog as a tab to allow to manage the related calendar events
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Events.CalendarEventGridPanel
 */
Tine.Events.CalendarEventGridPanel = Ext.extend(Tine.widgets.grid.BbarGridPanel, {

    /**
     * record class
     * @cfg {Tine.Addressbook.Model.Contact} recordClass
     */
    recordClass: Tine.Calendar.Model.Event,
    recordProxy: null,

    editDialogClass: Tine.Events.CalendarEventEditDialog,
    disableDeleteConfirmation: true,

    /**
     * grid specific
     * @private
     */
    defaultSortInfo: {field: 'dtstart', direction: 'ASC'},

    initComponent: function() {
        this.store = new Ext.data.JsonStore({
            // TODO prevent store from LOADing on render/creation (autoLoad = false does not help)
            //autoLoad: false,
            fields: Tine.Calendar.Model.Event
        });

        if (! this.app) {
            // FIXME this should not be needed ...
            this.app = Tine.Tinebase.appMgr.get('Events');
        }

        // TODO make it work: this currently destroys the container selector in the dialog because the container displayfield
        // TODO ... in the details panel is used instead of the selector :((
        // this.initDetailsPanel();

        // TODO use better fitting default columns? custom fields?
        var additionalColumns = [
            {
                id: 'type',
                header: this.app.i18n._("Type"),
                width: 100,
                dataIndex: 'type',
                renderer: function(value, metadata, record) {
                    return this.typeRenderer(record);
                },
                scope: this
            }
        ];
        this.gridConfig.cm = Tine.Calendar.GridView.initCM(Tine.Tinebase.appMgr.get('Calendar'), additionalColumns);

        Tine.Events.CalendarEventGridPanel.superclass.initComponent.call(this);

        this.store.on('add', this.updateContainer, this);
    },

    /**
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.Calendar.EventDetailsPanel({
            grid : this,
            app: this.app
        });
    },

    /**
     * TODO is this really needed?
     */
    updateContainer: function(store, records) {

        Ext.each(records, function(record) {
            // TODO use set()? but we need to suspend events ...
            record.data.container_id = Tine.Events.registry.get('defaultEventsCalendar');
        }, this);
    },

    /**
     * get events from record relations and put into store
     */
    fillEventsStore: function(record) {
        this.record = record;

        var calEvents = [],
            calEvent;

        Ext.each(record.get('relations'), function(relation) {
            if (['MAIN', 'ASSEMBLY', 'DISASSEMBLY'].indexOf(relation.type) !== false) {
                // only add "special" events to this grid and set main event here
                calEvent = new Tine.Calendar.Model.Event(relation.related_record, relation.related_id);
                if (relation.type === 'MAIN') {
                    this.mainEvent = calEvent;
                }
                calEvents.push(calEvent);
            }
        }, this);

        if (calEvents.length > 0) {
            this.getStore().suspendEvents();
            this.getStore().removeAll();
            this.getStore().add(calEvents);
            this.getStore().resumeEvents();
        }
    },

    /**
     * fetch events from grid and put into relations
     */
    updateRelationsFromGrid: function(currentRelations) {
        var relations = [];
        // remove all old cal_event relations
        Ext.each(currentRelations, function(relation) {
            if (['MAIN', 'ASSEMBLY', 'DISASSEMBLY'].indexOf(relation.type) === false) {
                relations.push(relation);
            }
        }, this);

        // add cal_event relations from events grid
        this.getStore().each(function(calEvent) {
            relations.push(this.getRelation(calEvent).data);
        }, this);

        return relations;
    },

    /**
     * create new record
     * - overwritten Tine.widgets.grid.GridPanel::createNewRecord
     *
     * @returns {Tine.Tinebase.data.Record}
     */
    createNewRecord: function() {
        var record = Tine.Events.CalendarEventGridPanel.superclass.createNewRecord.call(this);
        record.set('container_id', Tine.Events.registry.get('defaultEventsCalendar'));

        return record;
    },

    /**
     * get relation for cal event
     *
     * @param {Tine.Calendar.Model.Event} calEvent
     * @returns {Tine.Tinebase.Model.Relation}
     */
    getRelation: function(calEvent) {
        var type = this.getRelationType(calEvent);

        // set id to 0 for new events
        var eventId = calEvent.getId();
        if (eventId && eventId.match(/^new-/)) {
            calEvent.data.id = null;
        }

        var relationId = null,
            relatedCalEventId = calEvent.data.id ? calEvent.data.id : null;
        if (this.record && relatedCalEventId) {
            // try to find find existing relation as it is needed to update related record
            Ext.each(this.record.get('relations'), function (relation) {
                if (relation.related_id === relatedCalEventId) {
                    relationId = relation.id;
                }
            });
        }

        return new Tine.Tinebase.Model.Relation({
            related_record: calEvent.data,
            related_id: relatedCalEventId,
            related_model: 'Calendar_Model_Event',
            type: type,
            related_degree: 'parent',
            own_backend: 'Sql',
            related_backend: 'Sql',
            own_id: (this.record) ? this.record.id : null,
            own_model: 'Events_Model_Event',
            id: relationId
        });
    },

    /**
     * determines relation type (MAIN, ASSEMBLY, DISASSEMBLY) depending on dtstart & main event
     *
     * @param calEvent
     * @returns {string}
     */
    getRelationType: function(calEvent) {
        if (! this.mainEvent) {
            this.mainEvent = calEvent;
        }

        // normalize dtstarts
        var mainDtStart = (Ext.isString(this.mainEvent.get('dtstart')))
            ? new Date(this.mainEvent.get('dtstart'))
            : this.mainEvent.get('dtstart'),
            calEventDtStart = (Ext.isString(calEvent.get('dtstart')))
            ? new Date(calEvent.get('dtstart'))
            : calEvent.get('dtstart');
        mainDtStart = mainDtStart.getTime();
        calEventDtStart = calEventDtStart.getTime();

        if (calEventDtStart > mainDtStart) {
            return 'DISASSEMBLY'; // _('DISASSEMBLY')
        } else if (calEventDtStart < mainDtStart) {
            return 'ASSEMBLY'; // _('ASSEMBLY')
        } else if (calEventDtStart == mainDtStart) {
            return 'MAIN'; // _('MAIN')
        }

        return '';
    },

    getMainEvent: function() {
        return this.mainEvent;
    },

    /**
     * @param calEvent
     * @returns {string}
     */
    typeRenderer: function(calEvent) {
        var type = this.getRelationType(calEvent);
        return this.app.i18n._(type);
    },

    /**
     * updates / creates main event from form values
     */
    updateMainEvent: function(dtstart, dtend, summary) {
        if (! this.mainEvent) {
            this.mainEvent = new Tine.Calendar.Model.Event(Tine.Calendar.Model.Event.getDefaultData());
            this.mainEvent.set('container_id', Tine.Events.registry.get('defaultEventsCalendar'));
            this.mainEvent.id = 'new-' + Ext.id();
            this.mainEvent.setId(this.mainEvent.id);
        }

        if (dtstart && dtend) {
            this.mainEvent.set('dtstart', dtstart);
            this.mainEvent.set('dtend', dtend);
            this.mainEvent.set('summary', this.app.i18n._('Main Event') + ': ' + summary);
            // TODO use a better check to detect main event
            if (this.getStore().getCount() === 0) {
                this.getStore().add([this.mainEvent]);
            }
        }
    }
});
