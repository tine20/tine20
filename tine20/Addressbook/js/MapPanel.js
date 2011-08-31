/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/

Ext.ns('Tine.Addressbook');

Tine.Addressbook.MapPanel = Ext.extend(Ext.Panel, {
    
    /**
     * @property
     */    
    activeMapCard: null,
    
    record: null,
    
    layout: 'fit',
    frame: true,
    
    companyMap: null,
    privateMap: null,
    
    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get('Addressbook');
        
        this.title = this.app.i18n._('Map');

        this.defaults = {
            border: false
        };
        
        this.mapCards = new Ext.Panel({
            layout: 'card',
            activeItem: 0,
            items: []
        });

        this.idPrefix = Ext.id();
        
        this.tbar = [{
        	id: this.idPrefix + 'tglbtn' + 'companyMap',
        	enableToggle: true,
        	allowDepress: false,
            text: this.app.i18n._('Company address'),
            handler: this.onMapChange.createDelegate(this, ['companyMap']),
            toggleGroup: this.idPrefix + 'maptglgroup'
        }, ' ', {
        	id: this.idPrefix + 'tglbtn' + 'privateMap',
        	enableToggle: true,
        	allowDepress: false,
            text: this.app.i18n._('Private address'),
            handler: this.onMapChange.createDelegate(this, ['privateMap']),
            toggleGroup: this.idPrefix + 'maptglgroup'
        }];
        
        this.items = this.mapCards;
        
        Tine.Addressbook.MapPanel.superclass.initComponent.call(this);
    },
    
    /**
     * Change active map card
     * 
     * @param {String} map
     */
    onMapChange: function (map) {
        this.mapCards.layout.setActiveItem(this[map]);
        this.mapCards.layout.layout();
        this.activeMapCard = this[map];
    },
    
    onRecordLoad: function (record) {
        this.record = record;
        
        var adrOne = ! Ext.isEmpty(this.record.get('adr_one_lon')) && ! Ext.isEmpty(this.record.get('adr_one_lat')),
        	adrTwo = ! Ext.isEmpty(this.record.get('adr_two_lon')) && ! Ext.isEmpty(this.record.get('adr_two_lat')),
        	
        	btnOne = Ext.getCmp(this.idPrefix + 'tglbtn' + 'companyMap'),
        	btnTwo = Ext.getCmp(this.idPrefix + 'tglbtn' + 'privateMap');
        
        if (adrOne) {
        	if (! this.companyMap) {
        		Tine.log.debug('Add company address map');
        		this.companyMap = new Tine.widgets.MapPanel({
        			map: 'companyMap',
                	layout: 'fit',
                	zoom: 15        
            	});
            	this.mapCards.add(this.companyMap);
            	this.mapCards.doLayout();
        	}
        	
        	Tine.log.debug('Loading company address map coordinates');
        	this.companyMap.setCenter(this.record.get('adr_one_lon'), this.record.get('adr_one_lat'));
        }
        	
		if (adrTwo) {
        	Tine.log.debug('Add private address map');
        	if (! this.privateMap) {
        		this.privateMap = new Tine.widgets.MapPanel({
        			map: 'privateMap',
                	layout: 'fit',
                	zoom: 15        
            	});
            	this.mapCards.add(this.privateMap);
            	this.mapCards.doLayout();
        	}
        	
        	Tine.log.debug('Loading private address map coordinates');
        	this.privateMap.setCenter(this.record.get('adr_two_lon'), this.record.get('adr_two_lat'));      	
        }
        	
        btnOne.toggle(adrOne);
        btnOne.setDisabled(! adrOne);
        
        btnTwo.toggle(! adrOne && adrTwo);
        btnTwo.setDisabled(! adrTwo);
    },
    
    onRecordUpdate: function (record) {
    	
    }
});
