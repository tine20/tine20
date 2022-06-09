/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Timetracker.Model');

Tine.Timetracker.Model.TimesheetMixin = {
    statics: {
        getDefaultData(defaults) {
            // dd from modelConfig
            const dd = Tine.Tinebase.data.Record.getDefaultData(Tine.Timetracker.Model.Timesheet, defaults);

            // specific defaults
            return Object.assign({
                account_id: Tine.Tinebase.registry.get('currentAccount'),
                start_date: new Date()
            }, dd);
        }
    }
}
