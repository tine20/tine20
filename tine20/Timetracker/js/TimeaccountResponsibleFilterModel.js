/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Timetracker');

/**
 * @namespace   Tine.Timetracker
 * @class       Tine.Timetracker.TimeaccountResponsibleFilterModel
 * @extends     Tine.widgets.grid.ForeignRecordFilter
 *
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
Tine.Timetracker.TimeaccountResponsibleFilterModel = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {

    // private
    field: 'responsible',
    valueType: 'relation',

    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Timetracker');
        this.label = this.app.i18n._('Responsible');
        this.foreignRecordClass = 'Addressbook.Contact',
            this.pickerConfig = {emptyText: this.app.i18n._('without responsible'), allowBlank: true};

        Tine.Timetracker.TimeaccountResponsibleFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['timetracker.timeaccountresponsible'] = Tine.Timetracker.TimeaccountResponsibleFilterModel;