<?php
/**
 * postal.xwindow view
 *
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
     <script src="Tinebase/js/node_modules/lodash/index.js"></script>
     <script src="Tinebase/js/node_modules/jquery/dist/jquery.js"></script>
     <script src="Tinebase/js/node_modules/postal/lib/postal.js"></script>
     <script src="Tinebase/js/node_modules/postal.federation/lib/postal.federation.js"></script>
     <script src="Tinebase/js/node_modules/postal.request-response/lib/postal.request-response.js"></script>
     <script src="library/Store/store2.js"></script>
     <script src="library/Store/store.bind.js"></script>
     <script src="Tinebase/js/node_modules/postal.xwindow/lib/postal.xwindow.js"></script>
  </head>
  <body>
    <div>
      <input type="text" value="data.changed" id="topic"> <br />
      <textarea rows="4" cols="50" id="message">This message was send with love from an external thirdparty Tine 2.0 xwindow client</textarea><br />
      <br />
      <input type="button" value="Send Message" id="sendMessage">
      <input type="button" value="Clear" id="clear">
      <input type="button" value="Disconnect" id="disconnect">
    </div>

    <div id="messages"></div>


    <script>
        var config = postal.fedx.transports.xwindow.configure();
        postal.fedx.transports.xwindow.configure( {
            localStoragePrefix: "<?php echo Tinebase_Core::getUrl('path') . '/Tine.'; ?>" + config.localStoragePrefix
        } );

        // We need to tell postal how to get a deferred instance
        postal.configuration.promise.createDeferred = function() {
            return new $.Deferred();
        };
        // We need to tell postal how to get a "public-facing"/safe promise instance
        postal.configuration.promise.getPromise = function(dfd) {
            return dfd.promise();
        };

        postal.instanceId('xwindow-' + _.random(0,1000));
        postal.fedx.addFilter([
            { channel: 'thirdparty',   topic: '#', direction: 'both' },
//            { channel : 'postal.request-response', topic : '#', direction : 'both'}
        ]);
        postal.subscribe( {
            channel: "thirdparty",
            topic: "#",
            callback : function ( d, e ) {
                $( "#messages" ).append( "<div><pre>" + JSON.stringify( e, null, 4 ) + "</pre></div>" );
            }
        } );

        $(function() {
            $( "#clear" ).on( "click", function() {
                $( "#messages" ).html( "" );
                postal.publish( {
                    channel: "thirdparty",
                    topic: "clear"
                } );
            } );
            $("#sendMessage").on('click', function(){
                postal.publish({
                    channel: "thirdparty",
                    topic: $("#topic").val(),
                    data: $("#message").val()
                });
            });
            $( "#disconnect" ).on( "click", function() {
                postal.fedx.disconnect( { } );
            } );

            $("#msg3").on('click', function(){
                postal.fedx.disconnect();
            });
            postal.fedx.signalReady();
        });
    </script>
  </body>
</html>