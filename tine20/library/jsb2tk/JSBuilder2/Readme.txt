JSBuilder2 is a JavaScript and CSS project build tool.
For additional information, see http://extjs.com/products/jsbuilder/

JSBuilder version 2.0.0

Available arguments:
    --projectFile -p   (REQUIRED) Location of a jsb2 project file
    --homeDir -d       (REQUIRED) Home directory to build the project to
    --verbose -v       (OPTIONAL) Output detailed information about what is being built
    --debugSuffix -s   (OPTIONAL) Suffix to append to JS debug targets, defaults to 'debug'
    --help -h          (OPTIONAL) Prints this help display.

Example Usage:

Windows
java -jar JSBuilder2.jar --projectFile C:\Apps\www\ext3svn\ext.jsb2 --homeDir C:\Apps\www\deploy\

Linux and OS X
java -jar JSBuilder2.jar --projectFile /home/aaron/www/trunk/ext.jsb2 --homeDir /home/aaron/www/deploy/

JSBuilder uses the following libraries
--------------------------------------
YUI Compressor licensed under BSD License
http://developer.yahoo.com/yui/compressor/
http://developer.yahoo.com/yui/license.html

Mozilla's Rhino Project licensed under Mozilla's MPL
http://www.mozilla.org/rhino/
http://www.mozilla.org/MPL/

JArgs licensed under BSD License
http://jargs.sourceforge.net/

JSON in Java licensed under the JSON License
http://www.json.org/java/index.html
http://www.json.org/license.html
