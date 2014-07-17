// Backport from Ext 4.0
Ext.applyIf(Function.prototype, {

    /**
     * <p>Creates a delegate function, optionally with a bound scope which, when called, buffers
     * the execution of the passed function for the configured number of milliseconds.
     * If called again within that period, the impending invocation will be canceled, and the
     * timeout period will begin again.</p>
     *
     * @param {Function} fn The function to invoke on a buffered timer.
     * @param {Number} buffer The number of milliseconds by which to buffer the invocation of the
     * function.
     * @param {Object} scope (optional) The scope (<code><b>this</b></code> reference) in which
     * the passed function is executed. If omitted, defaults to the scope specified by the caller.
     * @param {Array} args (optional) Override arguments for the call. Defaults to the arguments
     * passed by the caller.
     * @return {Function} A function which invokes the passed function after buffering for the specified time.
     */
    createBuffered: function(fn, buffer, scope, args) {
        return function(){
            var timerId;
            return function() {
                var me = this;
                if (timerId) {
                    clearInterval(timerId);
                    timerId = null;
                }
                timerId = setTimeout(function(){
                    fn.apply(scope || me, args || arguments);
                }, buffer);
            };
        }();
    }
});
