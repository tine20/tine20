/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.namespace('Tine.widgets');

/**
 * lang chooser widget
 */
Tine.widgets.LangChooser = Ext.extend(Ext.form.ComboBox, {

    displayField: 'language',
    valueField: 'locale',
    triggerAction: 'all',
    width: 100,
    
    initComponent: function() {
        this.value = Tine.Tinebase.Registry.get('locale').language;
        this.store = new Ext.data.JsonStore({
            id: 'locale',
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Tinebase.Model.Language,
            baseParams: {
                method: 'Tinebase.getAvailableTranslations',
            }
        });
        Tine.widgets.LangChooser.superclass.initComponent.call(this);
        
        this.on('select', this.onLangSelect, this);
    },
    onLangSelect: function(combo, localeRecord, idx) {
        Ext.MessageBox.wait(_('setting new language...'), _('Please Wait!'));
        var locale = localeRecord.get('locale');
        Ext.Ajax.request({
            params: {
                method: 'Tinebase.setLocale',
                locale: locale,
                saveaspreference: false
            },
            success: function(result, request){
                window.location = window.location;
            }
        });
    }
});

