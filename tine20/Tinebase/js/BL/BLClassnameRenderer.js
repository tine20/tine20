/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Tine.widgets.grid.RendererManager.register('Tinebase', 'BLConfig', 'classname', function(classname) {
    var recordClass = Tine.Tinebase.data.RecordMgr.get(classname);
    return recordClass ? recordClass.getRecordName() : classname;
});