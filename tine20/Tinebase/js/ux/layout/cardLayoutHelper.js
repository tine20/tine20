Ext.ns('Ext.ux.layout.CardLayout');

Ext.ux.layout.CardLayout.helper = {
    /**
     * remove all items which should not be keeped -> don't have a keep flag
     *
     * @param {Ext.Panel} cardPanel
     */
    cleanupCardPanelItems: function (cardPanel) {
        if (cardPanel.items) {
            for (var i = 0, p; i < cardPanel.items.length; i++) {
                p = cardPanel.items.get(i);
                if (!p.keep) {
                    cardPanel.remove(p);
                }
            }
        }
    },

    /**
     * add or set given item
     *
     * @param {Ext.Panel} cardPanel
     * @param {Ext.Panel} item
     */
    setActiveCardPanelItem: function (cardPanel, item, keep) {
        // auto cleanup
        item.keep = !!keep;
        Ext.ux.layout.CardLayout.helper.cleanupCardPanelItems(cardPanel);

        if (cardPanel.items.indexOf(item) < 0) {
            cardPanel.add(item);
        }

        if (Ext.isFunction(cardPanel.layout.setActiveItem)) {
            cardPanel.layout.setActiveItem(item.id);
        } else {
            cardPanel.activeItem = cardPanel.items.indexOf(item);
        }

        cardPanel.doLayout();
    }
};
