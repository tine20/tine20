/**
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
    
    /**
     * @cfg {Sring}
     */
    fieldLabel: null,
    
    displayField: 'language',
    valueField: 'locale',
    triggerAction: 'all',
    width: 100,
    listWidth: 200,
    
    /**
     * @private
     */
    locationLoaded: {
        'generic': true,
        'tine' : true,
        'ext' : true
    },
    
    initComponent: function() {
        this.value = Tine.Tinebase.Registry.get('locale').language;
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
        var currentLocale = Tine.Tinebase.Registry.get('locale').locale;
        var newLocale = localeRecord.get('locale');
        
        if (newLocale != currentLocale) {
            Ext.MessageBox.wait(_('setting new language...'), _('Please Wait'));
            
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Tinebase.setLocale',
                    localeString: newLocale,
                    saveaspreference: true
                },
                success: function(result, request){
                    var responseData = Ext.util.JSON.decode(result.responseText);
                    window.location = window.location;
                    //this.loadNewLang(responseData.locale, responseData.translationFiles);
                }
            });
        }
    },
    /**
     * @too introduce timeout!
     */
    loadNewLang: function(locale, translationFiles) {
        Ext.MessageBox.wait(_('loading new language...'), _('Please Wait'));
        
        Tine.Tinebase.Registry.add('locale', locale);
        for (var location in this.locationLoaded) {
            this.locationLoaded[location] = false;
        }
        
        var headEl = Ext.get(document.getElementsByTagName("head")[0]);
        var file;
        var script = {};
        for (var location in translationFiles) {
            file = translationFiles[location];
            script[location] = Ext.DomHelper.insertFirst(headEl, {tag: 'script', src: file, type: 'text/javascript'}, true)
            script[location].on('load', this.onLangFileLoad, this);
        }
    },
    onLangFileLoad: function(e, scriptTag) {
        var path = scriptTag.src;
        if (path.match(/\/Tinebase\/js\/Locale\/static\/generic/)) {
            this.locationLoaded.generic = true;
        } else if (path.match(/\/Tinebase\/js\/Locale\/build\//)) {
            this.locationLoaded.tine = true;
        } else if (path.match(/\/ExtJS\/build\/locale\/ext-lang/)) {
            this.locationLoaded.ext = true;
        }
        
        // en has no tine translations!
        if (Tine.Tinebase.Registry.get('locale').locale == 'en') {
            this.locationLoaded.tine = true;
        }
        
        // wait till all translations are loaded
        var loadingCompleted = true;
        for (var location in this.locationLoaded) {
            if (! this.locationLoaded[location]) {
                loadingCompleted = false;
            }
        }
        
        if (loadingCompleted) {
            // not working, it might have something to do with all the existing 
            // elements :-(
            var body = document.getElementsByTagName("body")[0];
            body.innerHTML = '';
            Tine.Tinebase.MainScreen = new Tine.Tinebase.MainScreenClass();
            Tine.Tinebase.MainScreen.render();
            window.focus();
        }
    }
});

