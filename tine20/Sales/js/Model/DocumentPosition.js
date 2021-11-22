/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales.Model');

Tine.Sales.Model.DocumentPosition_AbstractMixin = {
    parentOnly() {
        console.error('parentOnly');
    },
    parent() {
        console.error('parent');
    },

    setFromProduct(product) {
        const productData = product.data || product;
        [].red
        const genericFieldNames = Tine.Tinebase.Model.modlogFields.reduce((a, f) => {return a.concat(f.name);}, [this.constructor.getMeta('idProperty')]);
        Object.keys(productData).forEach((fieldName) => {
            if (genericFieldNames.indexOf(fieldName) < 0 && this.constructor.hasField(fieldName)) {
                this.set(fieldName, productData[fieldName]);
            }
        });

        this.set('type', 'PRODUCT');
        this.set('title', productData.name);
        this.set('product_id', productData);
        this.set('quantity', 1);
        this.set('position_discount_type', 'SUM');
        this.set('position_discount_percentage', 0);
        this.set('position_discount_sum', 0);
        this.set('unit_price', productData.salesprice||0);
        this.set('sales_tax', productData.salestax||0);
        this.set('grouping', productData.default_grouping);
        this.set('sorting', productData.default_sorting);

        this.computePrice();
        this.commit();
    },

    computePrice() {
        if (this.isProductType()) {
            const price = this.get('unit_price') * this.get('quantity');
            this.set('position_price', price);
            const discount = this.get('position_discount_type') === 'SUM' ? this.get('position_discount_sum') :
                (price / 100 * this.get('position_discount_percentage'));
            const net = price - discount;
            this.set('net_price', net);
            const tax = net / 100 * this.get('sales_tax_rate');
            this.set('sales_tax', tax);
            this.set('gross_price', net + tax);
        }
    },

    isProductType() {
        return ['PRODUCT', 'ALTERNATIVE', 'OPTIONAL'].indexOf(this.get('type')) >= 0;
    },

    statics: {
        parentOnly() {
            console.error('parentOnlyStatic');
        },
        parent() {
            console.error('parentStatic');
        },
    }
}

Tine.Sales.Model.DocumentPosition_OfferMixin = {
    parent() {
        console.error('child');
    },
    statics: {
        parent() {
            console.error('childStatic');
        },
    }
};

// @TODO this should be done by modelConfig!
_.defaultsDeep(Tine.Sales.Model.DocumentPosition_OfferMixin, Tine.Sales.Model.DocumentPosition_AbstractMixin);
