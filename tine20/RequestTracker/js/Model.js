/*
 * Tine 2.0
 * 
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.RequestTracker.Model');

Tine.RequestTracker.Model.ticketArray = [
    { name: 'id' },
    { name: 'Queue' },
    { name: 'Owner' },
    { name: 'Creator' },
    { name: 'Subject'},
    { name: 'Status' },
    { name: 'Priority' },
    { name: 'InitialPriority' },
    { name: 'FinalPriority' },
    { name: 'Requestors' },
    { name: 'Cc' },
    { name: 'AdminCc' },
    { name: 'Created', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'Starts', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'Started', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'Due', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'Resolved', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'Told', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'LastUpdated', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'TimeEstimated' },
    { name: 'TimeWorked' },
    { name: 'History' }
];

Tine.RequestTracker.Model.Ticket = Tine.Tinebase.data.Record.create(Tine.RequestTracker.Model.ticketArray, {
    appName: 'RequestTracker',
    modelName: 'Ticket',
    titleProperty: 'Subject',
    // ngettext('Ticket', 'Tickets', n);
    recordName: 'Ticket',
    recordsName: 'Tickets'
});

Tine.RequestTracker.ticketBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'RequestTracker',
    modelName: 'Ticket',
    recordClass: Tine.RequestTracker.Model.Ticket
});
