/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Ext.ux');


/**
 * @namespace   Ext.ux
 * @class       Ext.ux.TabPanelStripCompressorPlugin
 *
 * @constructor
 * @param {Object} config Configurations options
 */
Ext.ux.TabPanelStripCompressorPlugin = function(config) {
    Ext.apply(this, config);
};

Ext.ux.TabPanelStripCompressorPlugin.prototype = {

    /**
     * tabpanel
     * @type object
     */
    tabpanel: null,

    /**
     * init this plugin
     *
     * @param {Ext.Component} cmp
     */
    init: function(cmp){
        this.tabpanel = cmp;
        this.originalWidth = {};
        this.compressedWidth = {};
        this.compressed = [];

        this.tabpanel.on('render', this.onRender, this);
    },

    onRender: function(tabpanel) {
        this.tabpanel.ownerCt.on('resize', this.onResize, this);
        this.tabpanel.on('add', this.onResize, this, {buffer: 100});
        this.tabpanel.on('remove', this.onResize, this, {buffer: 100});
        this.tabpanel.on('tabchange', this.onResize, this);
        this.onResize();
    },

    onResize: function() {
        active = this.tabpanel.strip.down('li.x-tab-strip-active');
        if (active) {
            Ext.fly(active).child('.x-tab-strip-inner', true).style.width = 'auto';
            var w = Ext.fly(active).child('.x-tab-strip-inner').getWidth();
            Ext.fly(active).child('.x-tab-strip-inner', true).style.width = w + 'px';
        }

        var count = this.tabpanel.items.length,
            ce = this.tabpanel.tabPosition != 'bottom' ? 'header' : 'footer',
            ow = this.tabpanel[ce].dom.offsetWidth,
            aw = this.tabpanel[ce].dom.clientWidth,
            edge = this.tabpanel.strip.down('li.x-tab-edge').getBox().right,
            lis = this.tabpanel.strip.query('li:not(.x-tab-edge)');

        this.saveOriginalWidths(lis);

        if (edge > aw) {
            this.compress(lis, edge-aw);
        } else {
            this.expand(lis, aw-edge);
        }
    },

    compress: function(lis, by) {
        var _ = window.lodash,
            me = this,
            toCompress = me.getDynamic(lis),
            potential = _.reduce(toCompress, function(p, li) {
                return p.concat(Ext.fly(li).child('.x-tab-strip-inner', true).offsetWidth - 16);
            }, []),
            widths = me.getCompressedWidth(potential, by);

        _.each(toCompress, function(li, i) {
            var inner = Ext.fly(li).child('.x-tab-strip-inner', true);
            inner.style.width = (16 + widths[i]) + 'px';
        });
    },

    expand: function(lis, by) {
        var _ = window.lodash,
            me = this,
            toExpand = me.getDynamic(lis),
            potential = _.reduce(toExpand, function(p, li) {
                var originalWidth = me.originalWidth[li.id];
                return p.concat(Math.max(0, originalWidth - Ext.fly(li).child('.x-tab-strip-inner', true).offsetWidth));
            }, []),
            widths = me.getExpandWidth(potential, by);

        _.each(toExpand, function(li, i) {
            var originalWidth = me.originalWidth[li.id],
                inner = Ext.fly(li).child('.x-tab-strip-inner', true);
            inner.style.width = (originalWidth-widths[i]) + 'px';
        });
    },

    // beginn with largest
    getCompressedWidth: function(potential, by) {
        var _ = window.lodash,
            used = 0,
            map = _.groupBy(potential),
            sorted = _.uniq([].concat(potential).sort(function (a, b) {
                return a - b;
            })),
            currWidth = sorted[sorted.length-1],
            currWidhtCount = map[currWidth].length,
            d = Math.min(by, sorted.length > 1 ?
                ((sorted[sorted.length-1] - sorted[sorted.length-2]) * currWidhtCount) :
                (sorted[0] * currWidhtCount)
            ),
            widths = _.map(potential, function(w) {
                var newWidth = w;
                if (w == currWidth) {
                    newWidth = w-d/currWidhtCount;
                    used += w-newWidth;
                }
                return newWidth;
            });

        if (used < by && _.sum(widths) && used > 1) {
            // console.warn('widths: ' + widths + ' by: ' + by + ' used: ' + used)
            return this.getCompressedWidth(widths, by-used);
        }

        return widths;
    },

    // expand aquidistantly (all with potential at the same rate)
    getExpandWidth: function(potential, by) {
        var _ = window.lodash,
            used = 0,
            sorted = _.compact(_.uniq([].concat(potential).sort(function (a, b) {
                return a - b;
            }))),
            expandCount = sorted.length,
            currWidth = sorted[0],
            d = Math.min(by/expandCount, currWidth),
            widths = _.map(potential, function(w) {
                var newWidth = w;
                if (w) {
                    newWidth = w-d;
                    used += d;
                }
                return newWidth;
            });

        if (used < by && _.sum(widths) && used > 1) {
            // console.warn('widths: ' + widths + ' by: ' + by + ' used: ' + used)
            return this.getExpandWidth(widths, by-used);
        }

        return widths;
    },

    getDynamic: function(lis) {
        var _ = window.lodash,
            me = this;

        return _.filter(lis, function(li) {
            return !me.tabpanel.get(li.id.split('__')[1]).noCompress &&
                !Ext.fly(li).hasClass('x-tab-strip-active');
        });
    },
    saveOriginalWidths: function(lis) {
        for(var i = 0, len = lis.length; i < len; i++) {
            if (! this.originalWidth[lis[i].id] && !Ext.fly(lis[i]).hasClass('x-tab-strip-active')) {
                var text = Ext.fly(lis[i]).child('.x-tab-strip-text', true);
                this.originalWidth[lis[i].id] = text.offsetWidth;
            }
        }
    }
};

Ext.preg('ux.tabpanelstripcompressorplugin', Ext.ux.TabPanelStripCompressorPlugin);