Ext.ns('Ext.ux');

/**
 * plugin to insert additional registered items into a container
 *
 * @namespace   Ext.ux
 * @class       Ext.ux.ItemRegistry
 * @autor       Cornelius Weiss <c.weiss@metaways.de>
 * @license     BSD, MIT and GPL
 *
 * @example
// register 'additional item' for myDialog
Ext.ux.ItemRegistry.registerItem('myDialog', 'add-item-xtype', 20);

// in myDialog use itemRegistyPlugin
myDialog = Ext.extend(Ext.Container, {
    ...
    plugins: [{
        ptype: 'ux.itemregistry',
        key:   'myDialog'
    }]
})
 */
Ext.ux.ItemRegistry = function (config) {
    Ext.apply(this, config);
};

/**
 * @static
 * @private
 */
Ext.ux.ItemRegistry.itemMap = {};
Ext.ux.ItemRegistry.AUTO_ID = 1;

/**
 * registers an item for a given key
 * @static
 * 
 * @param {String|Ext.Component|Object} key
 * @param {String/Constructor/Object} item
 * @param {Number} pos (optional)
 */
Ext.ux.ItemRegistry.registerItem = function(key, item, pos) {
    var _ = window.lodash;

    if (_.isString(key)) {
        if (!Ext.ux.ItemRegistry.itemMap.hasOwnProperty(key)) {
            Ext.ux.ItemRegistry.itemMap[key] = [];
        }

        Ext.ux.ItemRegistry.itemMap[key].push({
            item: item,
            pos: pos
        });
    }

    else {
        // initialised component
        var dynKey = 'Ext.ux.ItemRegistry-' + ++Ext.ux.ItemRegistry.AUTO_ID,
            plugin = new Ext.ux.ItemRegistry({key: dynKey});

        Ext.ux.ItemRegistry.registerItem(dynKey, item, pos);

        key.plugins = key.plugins || [];
        key.plugins.push(plugin);

        if (_.get(key, 'items.items')) {
            plugin.init(key);
        }
    }
};

Ext.ux.ItemRegistry.prototype = {
    /**
     * @cfg {String} key
     * key the items are registered under. If no key is given, the itemId
     * of the component will be used
     */
    key: null,

    init: function(cmp) {
        this.cmp = cmp;

        if (! this.key) {
            this.key = cmp.getItemId();
        }
        
        // give static item pos to existing items
        this.cmp.items.each(function(item, idx) {
            if (! item.hasOwnProperty('registerdItemPos')) {
                item.registerdItemPos = idx * 10;
            }
        }, this);

        var regItems = Ext.ux.ItemRegistry.itemMap[this.key] || [];



        Ext.each(regItems, function(reg) {
            // key hat / -> find item defined by first part and register item,regItem,possuffix -> return
            var path = String(reg.pos).split('/');
            if (path.length > 1) {
                var idx = +path.shift(),
                    item = this.cmp.items.get(idx);

                if (item) {
                    // console.info(path.join('/'))
                    Ext.ux.ItemRegistry.registerItem(this.cmp.items.get(idx), reg.item, path.join('/'));
                } else {
                    console.warn('cannot register for path - ' + path.join('/'));
                }
                return;

            }


            var addItem = this.getItem(reg),
                addPos = null;

            if (! addItem) {
                console.warn('item not found');
                return;
            }

            // insert item 
            this.cmp.items.each(function(item, idx) {
                if (addItem.registerdItemPos < item.registerdItemPos) {
                    this.cmp.insert(idx, addItem);
                    addPos = idx;
                    return false;
                }
                return true;
            }, this);

            if (! Ext.isNumber(addPos)) {
                this.cmp.add(addItem);
            }
        }, this);
    },

    getItem: function(reg) {
        var def = reg.item,
            item;
            
        if (typeof def === 'function') {
            try {
                item = new def(this.config);
            } catch (error) {
                console.error('Ext.ux.ItemRegistry::getItem failed to create');
                console.error(error);
                return;
            }
        } else {
            if (Ext.isString(def)) {
                def = {xtype: def};
            }
            
            item = this.cmp.lookupComponent(Object.assign(def, this.config));
        }

        item.registerdItemPos = reg.pos ? reg.pos : this.cmp.items.length * 10;
        
        return item;
    }
};
Ext.ComponentMgr.registerPlugin('ux.itemregistry', Ext.ux.ItemRegistry);

/* test - uncomment to run
if (! window.lodash) {
    window.lodash = _;
}

Ext.onReady(function() {
    var testWin = new Ext.Window({
        width: 640,
        height: 480,
        layout: 'fit',
        title: 'ux.itemregistry test',
        items: [{
            xtype: 'tabpanel',
            activeTab: 0,
            border: false,
            defaults: {border: false},
            itemId: 'testWin',
            plugins: ['ux.itemregistry'],
            items: [{
                title: 'basepanel',
                html: 'basepanel'
            }, {
                title: 'no pos',
                html: 'no pos'
            }, {
                title: 'pos 50',
                html: 'pos 50',
                registerdItemPos: 50
            }]
        }]
    });
    testWin.show();

    Ext.ux.ItemRegistry.registerItem(testWin.items.get(0), {
        xtype: 'panel',
        title: 'key-cmp',
        html: 'register item in an existing component'
    }, 80);
});

itemRegTestPanel20 = Ext.extend(Ext.Panel, {
    title: 'add panel pos 20',
    html: 'add panel pos 20',

    initComponent: function() {
        // example how to hook in owner
        this.on('added', function(me, owner, pos) {
            owner.on('tabchange', function() {
                console.log('tabchange');
            })
        }),

        itemRegTestPanel20.superclass.initComponent.call(this);
    }
});
Ext.ux.ItemRegistry.registerItem('testWin', itemRegTestPanel20, 20);

itemRegTestPanel60 = {
    xtype: 'panel',
    title: 'add panel pos 60',
    items: [{items: [{html: 'add panel pos 60'}]}]
};
Ext.ux.ItemRegistry.registerItem('testWin', itemRegTestPanel60, 60);
Ext.ux.ItemRegistry.registerItem(itemRegTestPanel60, {html: 'registered in component config'}, 20);
Ext.ux.ItemRegistry.registerItem('testWin', {html: 'registered with path position '}, '4/0/20');
*/
