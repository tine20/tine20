/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * SipPeers grid panel
 */
Tine.Voipmanager.AsteriskSipPeerGridPanel = Ext.extend(Tine.Tinebase.widgets.app.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.AsteriskSipPeer,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'name', direction: 'ASC'},
    gridConfig: {
        loadMask: true,
        autoExpandColumn: 'callerid'
    },
    
    initComponent: function() {
    
        this.recordProxy = Tine.Voipmanager.AsteriskSipPeerBackend;
                
        this.gridConfig.columns = this.getColumns();
        this.initFilterToolbar();
        this.actionToolbarItems = this.getToolbarItems();
        //this.initDetailsPanel();
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
         
        Tine.Voipmanager.AsteriskSipPeerGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
        	filterModels: [
        	    {label: _('Quick search'),    field: 'query',    operators: ['contains']},
        	    {label: this.app.i18n._('Name'), field: 'name' }
        	],
            defaultFilter: 'query',
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
    },    
    
      
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        return [{ 
	            id: 'id', 
	            header: this.app.i18n._('Id'), 
	            dataIndex: 'id', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'name', 
	            header: this.app.i18n._('name'), 
	            dataIndex: 'name', 
	            width: 50,
                sortable: true
            },{ 
	            id: 'accountcode', 
	            header: this.app.i18n._('account code'), 
	            dataIndex: 'accountcode', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'amaflags', 
	            header: this.app.i18n._('ama flags'), 
	            dataIndex: 'amaflags', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'callgroup', 
	            header: this.app.i18n._('call group'), 
	            dataIndex: 'callgroup', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'callerid', 
	            header: this.app.i18n._('caller id'), 
	            dataIndex: 'callerid', 
	            width: 80,
                sortable: true
            },{ 
	            id: 'canreinvite', 
	            header: this.app.i18n._('can reinvite'), 
	            dataIndex: 'canreinvite',
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'context', 
	            header: this.app.i18n._('context'), 
	            dataIndex: 'context',
	            width: 50,
                sortable: true
            },{ 
	            id: 'defaultip', 
	            header: this.app.i18n._('default ip'), 
	            dataIndex: 'defaultip', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'dtmfmode', 
	            header: this.app.i18n._('dtmf mode'), 
	            dataIndex: 'dtmfmode', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'fromuser', 
	            header: this.app.i18n._('from user'), 
	            dataIndex: 'fromuser', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'fromdomain', 
	            header: this.app.i18n._('from domain'), 
	            dataIndex: 'fromdomain', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'fullcontact', 
	            header: this.app.i18n._('full contact'), 
	            dataIndex: 'fullcontact', 
	            width: 200, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'host', 
	            header: this.app.i18n._('host'), 
	            dataIndex: 'host', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'insecure', 
	            header: this.app.i18n._('insecure'), 
	            dataIndex: 'insecure', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'language', 
	            header: this.app.i18n._('language'), 
	            dataIndex: 'language', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'mailbox', 
	            header: this.app.i18n._('mailbox'), 
	            dataIndex: 'mailbox', 
	            width: 30,
                sortable: true
            },{ 
	            id: 'md5secret', 
	            header: this.app.i18n._('md5 secret'), 
	            dataIndex: 'md5secret', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'nat', 
	            header: this.app.i18n._('nat'), 
	            dataIndex: 'nat', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'deny', 
	            header: this.app.i18n._('deny'), 
	            dataIndex: 'deny', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'permit', 
	            header: this.app.i18n._('permit'), 
	            dataIndex: 'permit', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'mask', 
	            header: this.app.i18n._('mask'), 
	            dataIndex: 'mask', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'pickupgroup', 
	            header: this.app.i18n._('pickup group'), 
	            dataIndex: 'pickupgroup', 
	            width: 40,
                sortable: true
            },{ 
	            id: 'port', 
	            header: this.app.i18n._('port'), 
	            dataIndex: 'port', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'qualify', 
	            header: this.app.i18n._('qualify'), 
	            dataIndex: 'qualify', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{
	            id: 'restrictcid', 
	            header: this.app.i18n._('restrict cid'), 
	            dataIndex: 'restrictcid', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'rtptimeout', 
	            header: this.app.i18n._('rtp timeout'), 
	            dataIndex: 'rtptimeout', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'rtpholdtimeout', 
	            header: this.app.i18n._('rtp hold timeout'), 
	            dataIndex: 'rtpholdtimeout', 
	            width: 30, 
                sortable: true,
	            hidden: true 
            },{ 
	            id: 'secret', 
	            header: this.app.i18n._('secret'), 
	            dataIndex: 'secret', 
	            width: 30, 
                sortable: true,
	            hidden: true
			},{ 
				id: 'type', 
				header: this.app.i18n._('type'), 
				dataIndex: 'type', 
				width: 30,
                sortable: true
			},{ 
				id: 'defaultuser', 
				header: this.app.i18n._('defaultuser'), 
				dataIndex: 'defaultuser', 
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'disallow', 
				header: this.app.i18n._('disallow'), 
				dataIndex: 'disallow', 
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'allow', 
				header: this.app.i18n._('allow'), 
				dataIndex: 'allow', 
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'musiconhold', 
				header: this.app.i18n._('music on hold'), 
				dataIndex: 'musiconhold', 
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'regseconds', 
				header: this.app.i18n._('reg seconds'), 
				dataIndex: 'regseconds', 
				width: 50,
                sortable: true, 
                renderer: Tine.Tinebase.common.dateTimeRenderer
			},{ 
				id: 'ipaddr', 
				header: this.app.i18n._('ip address'), 
				dataIndex: 'ipaddr', 
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'regexten', 
				header: this.app.i18n._('reg exten'), 
				dataIndex: 'regexten', 
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'cancallforward', 
				header: this.app.i18n._('can call forward'), 
				dataIndex: 'cancallforward', 
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'setvar', 
				header: this.app.i18n._('set var'), 
				dataIndex: 'setvar', 
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'notifyringing', 
				header: this.app.i18n._('notify ringing'), 
				dataIndex: 'notifyringing',
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'useclientcode', 
				header: this.app.i18n._('use client code'), 
				dataIndex: 'useclientcode', 
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'authuser', 
				header: this.app.i18n._('auth user'), 
				dataIndex: 'authuser', 
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'call-limit', 
				header: this.app.i18n._('call limit'), 
				dataIndex: 'call-limit', 
				width: 30, 
                sortable: true,
				hidden: true 
			},{ 
				id: 'busy-level', 
				header: this.app.i18n._('busy level'), 
				dataIndex: 'busy-level', 
				width: 30, 
                sortable: true,
				hidden: true 
			}];
    },
    
    initDetailsPanel: function() { return false; },
    
    /**
     * return additional tb items
     * 
     * @todo add duplicate button
     * @todo move export buttons to single menu/split button
     */
    getToolbarItems: function(){
        return [

        ];
    }
    
});