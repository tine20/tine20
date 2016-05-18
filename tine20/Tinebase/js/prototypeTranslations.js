/*
 * Tine 2.0
 * 
 * translations for js prototypes
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.Tinebase');

/**
 * this is called in tineInit::initLocale() to make sure we translate some plugin strings by overwriting prototype values
 */
Tine.Tinebase.prototypeTranslation = function() {
    // html editor plugin translations
    Ext.ux.form.HtmlEditor.IndentOutdent.prototype.midasBtns[1].tooltip.title = i18n._('Outdent Text');
    Ext.ux.form.HtmlEditor.IndentOutdent.prototype.midasBtns[1].overflowText = i18n._('Outdent Text');
    Ext.ux.form.HtmlEditor.IndentOutdent.prototype.midasBtns[2].tooltip.title = i18n._('Indent Text');
    Ext.ux.form.HtmlEditor.IndentOutdent.prototype.midasBtns[2].overflowText = i18n._('Indent Text');
    Ext.ux.form.HtmlEditor.RemoveFormat.prototype.midasBtns[1].tooltip.title = i18n._('Remove Formatting');
    Ext.ux.form.HtmlEditor.RemoveFormat.prototype.midasBtns[1].overflowText = i18n._('Remove Formatting');
}
