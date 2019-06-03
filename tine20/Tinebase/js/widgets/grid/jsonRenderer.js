/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.grid');


Tine.widgets.grid.jsonRenderer = function(value, metadata, record) {
    var result = Ext.isString(value) ? value : JSON.stringify(value);

    return Tine.widgets.grid.jsonRenderer.syntaxHighlight(result);
};

Tine.widgets.grid.jsonRenderer.displayField = function(value) {
    var result = Ext.isString(value) ? value : JSON.stringify(value, undefined, 4);

    return Tine.widgets.grid.jsonRenderer.syntaxHighlight(result);
};

Tine.widgets.grid.jsonRenderer.syntaxHighlight =function(json) {
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var cls = 'number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'key';
            } else {
                cls = 'string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'boolean';
        } else if (/null/.test(match)) {
            cls = 'null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
};