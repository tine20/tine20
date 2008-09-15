//(function() {

/**
 * opens connection and logs in to tine 2.0
 * 
 * @param {String} url 
 * @param {String} username
 * @param {String} password
 * @param {Function} success callback
 * @param {Function} fail callback
 * 
 */
window.tineClient = function(url, username, password, success, fail) {
    
    /**
     * @property {String} url
     */
    this.url = url;
    
    /**
     * @property {String} json key of session
     */
    this.jsonKey = null;
    
    /**
     * @property {Object} Current Account
     */
    this.account = null;
    
    onLogin = function(response) {
        if (response.status == 'success') {
            this.jsonKey = response.jsonKey;
            this.account = response.account;
            delete response.jsonKey;
            delete response.account;
            success(response);
        } else {
            fail(response);
        }
    };
    
    this.request('Tinebase.login', {
        username: username,
        password: password
    }, onLogin, onLogin);
    
};

window.tineClient.prototype = {
    /**
     * returns current account
     */
    getAccount: function() {
        return this.account;
    },
    
    /**
     * performs a generic request
     */
    request: function(method, params, success, fail, async) {
        async = async ? async : true;
        
        var req = new XMLHttpRequest();
        req.onerror = function() {

        };
        
        scope = this;
        req.onreadystatechange = function() {
            if (req.readyState == 4) {
                success.call(scope, JSON.decode(req.responseText));
            }
        };
    
        req.open("POST", this.url, async);
        req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
        //req.setRequestHeader("Content-Length", args.length);
        req.setRequestHeader("X-Tine20-Request-Type", "JSON");
        
        var send = 'method=' + method;
        for (var param in params) {
            send += '&' + param + '=' + params[param];
        }
        if (this.jsonKey) {
            send += '&jsonKey=' + this.jsonKey;
        }
        
        req.send(send);
    },
    
    /**
     * gets tasks
     */
    getTasks: function(filter, paging, cb) {
        this.request('Tasks.searchTasks', {
            filter: JSON.encode({
                containerType: 'all',
                query: '',
                due: false,
                container: null,
                organizer: null,
                tag: false,
                owner: null,
                sort: 'due',
                dir: 'ASC',
                start: 0,
                limit: 0,
                showClosed: false,
                statusId: ''
            })
        }, cb);
    }

};


addEventListener("load", function(event) {
//    var client = new tineClient('http://demo.tine20.org', 'tine20demo', 'demo');
    var onLogin = function(response) {
        client.getTasks('', '', function(response) {
            var tasks = response.results, task, html = '';
            for (var i=0; i<tasks.length; i++) {
                task = tasks[i];
                html += '<li><a href="#task_' + task.id + '">' + task.summary + '</a></li> \n';
            }
            document.getElementById('home').innerHTML = html;
        });
    }
    var client = new tineClient('/tt/tine20/index.php', 'tine20admin', 'lars', onLogin);
    
}, false);


//    addEventListener("load", function(event) {
//        /*! This creates the database tables. */
//        function createTables(db) {
//            /* To wipe out the table (if you are still experimenting with schemas,
//               for example), enable this block. */
//            if (1) {
//                db.transaction(
//                    function (transaction) {
//                    transaction.executeSql('DROP TABLE settings;');
//                    }
//                );
//            }
//            
//            db.transaction(
//                function (transaction) {
//                    transaction.executeSql('CREATE TABLE IF NOT EXISTS settings(id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, url TEXT NOT NULL, key TEXT NOT NULL, value TEXT);', [], nullDataHandler, killTransaction);
//                }
//            );
//        }
//        
//        /*! This is used as a data handler for a request that should return no data. */
//        function nullDataHandler(transaction, results) {
//        
//        }
//        
//        /*! When passed as the error handler, this silently causes a transaction to fail. */
//        function killTransaction(transaction, error) {
//            return true; // fatal transaction error
//        }
//        
//        /*! When passed as the error handler, this causes a transaction to fail with a warning message. */
//        function errorHandler(transaction, error) {
//            // Error is a human-readable string.
//            alert('Oops.  Error was '+error.message+' (Code '+error.code+')');
//        
//            // Handle errors here
//            var we_think_this_error_is_fatal = true;
//            if (we_think_this_error_is_fatal) return true;
//            return false;
//        }
//    
//    
//        var db = window.openDatabase('config', '0.1', 'Tine 2.0 iPhone Tasks Config', '5000');
//        createTables(db);
//        
//        db.transaction( 
//            function(transaction) {
//                var myfunc = new Function("transaction", "results", "/* alert('insert ID is'+results.insertId); */ transaction.executeSql('INSERT INTO files (name, filedata_id) VALUES (?, ?);', [ '"+name+"', results.insertId], nullDataHandler, killTransaction);");
//                transaction.executeSql('INSERT INTO settings (url, key, value) VALUES (?, ?, ?);', ['demo.tine20.org', 'nelius', 'conny']);
//                //transaction.executeSql('SELECT * FROM settings;', [], function(transaction, resultSet) {
//                //    console.log(resultSet);
//                //});
//            
//        });
//    }, false);



