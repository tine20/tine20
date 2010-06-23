Ext.applyIf(Array.prototype, {
    /**
     * Returns an array containing all the entries from this array that are not present in any of the other arrays.
     * 
     * @param {Array} array1
     * @param {Array} [array2]
     * @param {Array} [...]
     */
    diff: function() {
        var allItems = [],
            diffs = [];
        
        // create an array containing all items of all args
        for (var i=0; i<arguments.length; i++) {
            allItems = allItems.concat(arguments[i]);
        }
        
        // check which item is not present in all args
        Ext.each(this, function(item) {
            if (allItems.indexOf(item) < 0) {
                diffs.push(item);
            }
        }, this);
        
        
        return diffs;
    }
});

/*
var testArr = ["green", "red", "blue", "red"],
    diff1 = testArr.diff(["green", "yellow", "red"]),
    diff2 = testArr.diff(["green"], "yellow", "red", "blue");

if (diff1.length !== 1) console.error('Failed asserting that diff contains one entry');
if (diff1[0] !== "blue") console.error('Failed asserting that diff is "blue"');
if (diff2.length !== 0) console.error('Failed asserting that diff is empty');
*/