/**
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

Ext.ns('Tine.widgets');

/**
 * lang chooser widget
 *
 * @namespace   Tine.widgets
 * @class       Tine.widgets.LangChooser
 * @extends     Ext.form.ComboBox
 */
Tine.widgets.LangChooser = Ext.extend(Ext.form.ComboBox, {
    
    /**
     * @cfg {Sring}
     */
    fieldLabel: null,
    
    displayField: 'language',
    valueField: 'locale',
    triggerAction: 'all',
    width: 100,
    listWidth: 200,
    
    initComponent: function() {
        this.value = Tine.Tinebase.registry.get('locale').language;
        this.fieldLabel = this.fieldLabel ? this.fieldLabel : _('Language');
        
        this.tpl = new Ext.XTemplate(
            '<tpl for=".">' +
                '<div class="x-combo-list-item">' +
                    '{language} <tpl if="region.length &gt; 1">{region}</tpl> [{locale}]' + 
                '</div>' +
            '</tpl>',{
                encode: function(value) {
                    return Ext.util.Format.htmlEncode(value);
                }
            }
        );
        
        this.store = new Ext.data.JsonStore({
            id: 'locale',
            root: 'results',
            totalProperty: 'totalcount',
            fields: Tine.Tinebase.Model.Language,
            baseParams: {
                method: 'Tinebase.getAvailableTranslations'
            }
        });
        Tine.widgets.LangChooser.superclass.initComponent.call(this);
        
        this.on('select', this.onLangSelect, this);
    },
    
    onLangSelect: function(combo, localeRecord, idx) {
        var currentLocale = Tine.Tinebase.registry.get('locale').locale;
        var newLocale = localeRecord.get('locale');
        
        if (newLocale != currentLocale) {
            Ext.MessageBox.wait(_('setting new language...'), _('Please Wait'));
            
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Tinebase.setLocale',
                    localeString: newLocale,
                    saveaspreference: true,
                    setcookie: true
                },
                success: function(result, request){
                    if (window.google && google.gears && google.gears.localServer) {
                        var pkgStore = google.gears.localServer.openStore('tine20-package-store');
                        if (pkgStore) {
                            google.gears.localServer.removeStore('tine20-package-store');
                        }
                    }
                    window.location = window.location.href.replace(/#+.*/, '');
                }
            });
        }
    }
});

