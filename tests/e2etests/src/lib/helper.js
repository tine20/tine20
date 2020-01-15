module.exports = {
    getElement: function (type,page,text) {
        return page.$x("//" + type + "[contains(., '" + text + "')]");
    },
};