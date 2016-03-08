Ext.applyIf(Date.prototype, {
    /**
     * get time part in milliseconds
     * @returns {number}
     */
    getTimePart: function() {
        return 1000 * (3600 * this.getHours() + 60 * this.getMinutes() + this.getSeconds());
    }
});