/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Tine.widgets.grid.RendererManager.register('Tinebase', 'BLConfig', 'configRecord', function(configRecord, metaData, record) {
    var _ = window.lodash,
        recordClass = Tine.Tinebase.data.RecordMgr.get(record.get('classname'));

    if (! recordClass) {
        return _(configRecord)
    }

    if (! configRecord.data) {
        configRecord = Tine.Tinebase.data.Record.setFromJson(configRecord, recordClass);
    }

    return configRecord.getTitle();
});
