/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

let map = {
    // upstream supported actions
    actions: {
        view: [".pdf", ".djvu", ".xps"],
        edit: [".docx", ".xlsx", ".csv", ".pptx", ".txt"],
        convert: [".docm", ".doc", ".dotx", ".dotm", ".dot", ".odt", ".fodt", ".ott", ".xlsm", ".xls", ".xltx", ".xltm", ".xlt", ".ods", ".fods", ".ots", ".pptm", ".ppt", ".ppsx", ".ppsm", ".pps", ".potx", ".potm", ".pot", ".odp", ".fodp", ".otp", ".rtf", ".mht", ".html", ".htm", ".epub"]
    },
    // upstream supported types
    types: {
        spreadsheet: [".xls", ".xlsx", ".xlsm",
            ".xlt", ".xltx", ".xltm",
            ".ods", ".fods", ".ots", ".csv"],
        presentation: [".pps", ".ppsx", ".ppsm",
            ".ppt", ".pptx", ".pptm",
            ".pot", ".potx", ".potm",
            ".odp", ".fodp", ".otp"],
        text: [".doc", ".docx", ".docm",
            ".dot", ".dotx", ".dotm",
            ".odt", ".fodt", ".ott", ".rtf", ".txt",
            ".html", ".htm", ".mht",
            ".pdf", ".djvu", ".fb2", ".epub", ".xps"]
    }
};

const getType = function (name) {
    let type = null;
    const ext = ('.' + String(name).split('.').pop()).toLowerCase();

    _.each(map.types, (val, key) => {
        if (_.indexOf(val, ext) !== -1) {
            type = key;
            return false;
        }
    });

    return type;
};

const isEditable = function(name) {
    const hasType = getType(name);
    const ext = ('.' + String(name).split('.').pop()).toLowerCase();

    return hasType && _.indexOf(map.actions.view, ext) < 0;
};

export {
    getType,
    isEditable
};