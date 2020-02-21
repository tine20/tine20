module.exports = {
    getElement: function (type, page, text) {
        return page.$x("//" + type + "[contains(., '" + text + "')]");
    },
    getCurrenUser: function (page) {
        return page.evaluate(() => Tine.Tinebase.registry.get('currentAccount'));
    }
};