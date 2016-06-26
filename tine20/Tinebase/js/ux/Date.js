Ext.applyIf(Date.prototype, {
    /**
     * get time part in milliseconds
     * @returns {number}
     */
    getTimePart: function() {
        return 1000 * (3600 * this.getHours() + 60 * this.getMinutes() + this.getSeconds());
    }
});

Ext.applyIf(Date, {
    /**
     * returns date of timepart (starting at begin of time)
     *
     * @returns {Date}
     */
    parseTimePart: function(timeString, format) {
        var date = Date.parseDate(timeString, format),
            today = new Date().clearTime();

        return new Date(date-today);
    }
});