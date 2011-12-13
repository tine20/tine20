<?php
/**
 * jsbuilder2 php toolkit
 *
 * Copyright (c) 2010-2011, Metaways Infosystems GmbH
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted 
 * provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of conditions 
 * and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright notice, this list of conditions 
 * and the following disclaimer in the documentation and/or other materials provided with the 
 * distribution.
 *
 * Neither the name of Metaways Infosystems GmbH nor the names of its contributors may be used to 
 * endorse or promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR 
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND 
 * FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL 
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER 
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT 
 * OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
 /**
  * jsbuilder2 php toolkit
  *
  * toolkit for building and inclusion of js/css files based on descriptions files as used in 
  * http://www.sencha.com/products/jsbuilder/
  * 
  * Motivation:
  *  Javascript applications may consist of a large number of javascript and css files. The information
  *  which files exactly belong to an application or modul needs to be defined somewhere to:
  *  1. feed a javascript builder/compressor
  *  2. tell the IDE about classes/functions for documentation and code completion
  *  3. inlcude the files in the index html
  *  4. feed a javascipt dynamic code loader
  *  
  *  As for the first two points jsbuilder can already be used, the idea is to also use the jsb2 definitions
  *  for the latter two points.
  *
  *  Moreover there are several strategies how to include the js/css files in an index view which all
  *  have their spechial advantages/disadvantages in different scenarios. So the second target of this
  *  toolkit is to configure the include and deploy strategy at a single point, without the need
  *  of furthor adoptions.
  * 
  */
class jsb2tk
{
    /**
     * include each js/css file individually (note: no compression)
     */
    const INCLUDEMODE_INDIVIDUAL    = 'individual';

    /**
     * include one compressed js/css file for each defined package
     */
    const INCLUDEMODE_PACKAGE       = 'package';

    /**
     * include one uncompressed/debug js/css file for each defined package
     */
    const INCLUDEMODE_DEBUG_PACKAGE = 'debugpackage';

    /**
     * packages are expected to be statically/prebuild deployed
     */
    const DEPLOYMODE_STATIC   = 'static';

    /**
     * packages are build dynamically on demand
     */
    const DEPLOYMODE_DYNAMIC  = 'dynamic';

    /**
     * @var registered packages
     */
    protected $_registeredPkgs = array(
        'js'  => array(),
        'css' => array()
    );

    /**
     * append ctime on the html includes
     */
    protected $_appendctime = FALSE;
    
    /**
     * html indention
     */
    protected $_HTMLIndent = '    ';
    
    /**
     * @var include mode
     */
    protected $_includeMode = self::INCLUDEMODE_PACKAGE;

    /**
     * @var deploy mode
     */
    protected $_deployMode = self::DEPLOYMODE_STATIC;

    /**
     * @var jsbuilder 2 binary
     */
    protected $_jsb2bin = '/JSBuilder2/JSBuilder2.jar';
    
    /**
     * relative (to this file) include root for script/link tags
     * 
     * <script src="module/some.js" />
     *             |      |---- location of jsb2 file with some.js include
     *             |---- configured htmlTagRoot
     */
    protected $_htmlTagRoot = '../../';
    
    /**
     * @var string homedir for the builds
     */
    protected $_homeDir = NULL;
    
    /**
     * @var array registered modules
     */
    protected $_registeredModuls = array();
    
    /**
     * constructs a new jsb2 toolkit object
     * 
     * @param  array $_config
     */
    public function __construct($_config = array())
    {
        foreach ($_config as $key => $value) {
            $fn = 'set' . ucfirst($key);
            $this->$fn($value);
        }
        
        if (! array_key_exists('jsb2bin', $_config)) {
            $this->setJsb2bin(dirname(__FILE__) . "/{$this->_jsb2bin}");
        }
    }
    
    /**
     * returns the parsed content of a jsb2 file in a stdClass Object
     *
     * @param  string $_file file to parse
     * @return stdClass parsed contents
     */
    public static function getDefinition($_file)
    {
        $JSON = file_get_contents($_file);
        $def = json_decode($JSON);
        if ($jsonError = json_last_error() != JSON_ERROR_NONE) {
            throw new Exception("could not parse file $_file" , $jsonError);
        }
        return json_decode($JSON);
    }
    
    public function getHTML()
    {
        if ($this->_deployMode == self::DEPLOYMODE_DYNAMIC) {
            $this->_buildPackages(TRUE);
        }
                    
        $html = '';
        
        foreach (array('css', 'js') as $what) {
            switch ($this->_includeMode) {
                case self::INCLUDEMODE_INDIVIDUAL:
                    $files = $this->getIndividualFiles($what);
                    break;
                    
                case self::INCLUDEMODE_PACKAGE:
                case self::INCLUDEMODE_DEBUG_PACKAGE:
                    $files = array();
                    foreach ($this->_registeredPkgs[$what] as $pkg) {
                        $files[] = $this->getPackageFile($pkg, $this->_includeMode == self::INCLUDEMODE_DEBUG_PACKAGE);
                        
                    }
                    break;
            }
            
            foreach($files as $file) {
                if (! file_exists($file->path)) {
                    throw new Exception("required file {$file->path} is missing");
                }
                
                $fileURL = $file->url . ($this->_appendctime ? '?' . filectime($file->path) : '');
                
                switch ($what) {
                    case 'css':
                        $html .= $this->_HTMLIndent . '<link rel="stylesheet" type="text/css" href="' . $fileURL . '" />' . "\n";
                        break;
                    case 'js':
                        $html .= $this->_HTMLIndent . '<script type="text/javascript" src="' . $fileURL . '"></script>' . "\n";
                        break;
                }
            }
        }
        
        return $html;
    }
    
    /**
     * returns individual defined js/css files of all registered packages
     *
     * @param  string $_what js|css
     * @return array
     */
    public function getIndividualFiles($_what)
    {
        $files = array();
        
        foreach ($this->_registeredPkgs[$_what] as $pkg) {
            $files = array_merge($files, $this->getIncludedFiles($pkg));
        }
        
        return $files;
    }
    
    /**
     * returns included files of the given package
     * 
     * @param  object $pkg
     * @return array 
     */
    public function getIncludedFiles($_pkg)
    {
        $files = array();
        foreach ($_pkg->fileIncludes as $fileInclude) {
            $file = $fileInclude->path . $fileInclude->text;
            
            $fileObj = new stdClass();
            $fileObj->pkg = $_pkg;
            $fileObj->path = "{$_pkg->modul->basePath}/{$file}";
            $fileObj->url  = "{$_pkg->modul->baseURL}/{$file}";
            $files[] = $fileObj;
        }
        
        return $files;
    }
    
    /**
     * returns file of given package
     * 
     * @param  object $_pkg
     * @param  bool   $_debug
     * @return string
     */
    public function getPackageFile($_pkg, $_debug = FALSE)
    {
        $file = "{$_pkg->modul->deployDir}/{$_pkg->file}";
        
        if ($_debug && $_pkg->isDebug) {
            $file = preg_replace('/(?=\.js$)/', '-debug', $file);
        }
        
        $fileObj = new stdClass();
        $fileObj->pkg = $_pkg;
        $fileObj->path = "{$_pkg->modul->basePath}/{$file}";
        $fileObj->url  = "{$_pkg->modul->baseURL}/{$file}";
        
        return $fileObj;
    }
    
    /**
     * register packages from given jsb2 file
     *
     * @param  string $_file to register
     * @param  string $_baseURL baseurl pathes in jsb2 files refer to
     * @param  array  $_filter array of package names which should not be registered
     */
    public function register($_file, $_baseURL = '.' , $_filter = array())
    {
        $def = $this->getDefinition($_file);
        $def->jsb2file = $_file;
        $def->baseURL  = $_baseURL;
        $def->basePath = dirname($_file);
        
        $this->_registeredModuls[$_file] = $def;
        
        foreach ($def->pkgs as $pkg) {
            if (! in_array($pkg->name, $_filter)) {
                $pkg->modul   = $def;
                
                if (preg_match('/\.(js|css)$/', $pkg->file, $matches)) {
                    $what = $matches[1];
                    $this->_registeredPkgs[$what][] = $pkg;
                }
            }
        }
    }
    
    public function getRegisteredModules()
    {
        return $this->_registeredModuls;
    }
    
    /**
     * set deploy mode
     *
     * @param  string $_mode
     * @return jsb2tk $this
     */
    public function setDeploymode($_mode)
    {
        if (! in_array($_mode, array(self::DEPLOYMODE_STATIC, self::DEPLOYMODE_DYNAMIC))) {
            throw new Exception("unsupported deploy mode: '$_mode'");
        }
        
        $this->_deployMode = $_mode;
        return $this;
    }
    
    /**
     * set inlude mode
     *
     * @param  string $_mode
     * @return jsb2tk $this
     */
    public function setIncludemode($_mode) {
        if (! in_array($_mode, array(self::INCLUDEMODE_INDIVIDUAL, self::INCLUDEMODE_PACKAGE, self::INCLUDEMODE_DEBUG_PACKAGE))) {
            throw new Exception("unsupported inlcude mode: '$_mode'");
        }
        
        $this->_includeMode = $_mode;
        return $this;
    }
    
    /**
     * set jsbuilder2 binary path
     *
     * @param  string $_bin
     * @return jsb2tk $this
     */
    public function setJsb2bin($_bin) {
        $this->_jsb2bin = $_bin;
        return $this;
    }

    /**
     * append ctime for js/css includes?
     * 
     * @param  bool $_append
     * @return jsb2tk $this
     */
    public function setAppendctime($_append)
    {
        $this->_appendctime = (bool) $_append;
        return $this;
    }
    
    /**
     * indention for html output
     * 
     * @param  string $_indent
     * @return jsb2tk $this
     */
    public function setHtmlindention($_indent)
    {
        if (preg_match('/\S/', $_indent)) {
            throw new Exception('invalid indention');
        }
        
        $this->_HTMLIndent = $_indent;
        return $this;
    }
    
    /**
     * relative (to this file) include root for script/link tags
     * 
     * @param  string $_tagRoot
     * @return jsb2tk $this
     */
    public function setHtmlTagRoot($_tagRoot)
    {
        $this->_htmlTagRoot = (string) $_tagRoot;
        return $this;
    }
    
    /**
     * set homedir for the builds
     * 
     * @param  string $_homeDir
     * @return jsb2tk $this
     */
    public function setHomeDir($_homeDir)
    {
        if (! is_writable($_homeDir)) {
            throw new Exception('homedir is not writeable');
        }
        
        $this->_homeDir = $_homeDir;
        return $this;
    }
    
    /**
     * gets homedir for builds
     * 
     * @return string
     */
    public function getHomeDir()
    {
        if (! $this->_homeDir) {
            $homedir = sys_get_temp_dir() . '/jsb2tk';
            if (! is_dir($homedir)) {
                mkdir($homedir, 0600, TRUE);
            }
            
            $this->setHomeDir($homedir);
        }
        
        return $this->_homeDir;
    }
    
    public function buildAll()
    {
        foreach($this->getRegisteredModules() as $modul) {
            $this->buildModul($modul);
        }
    }
    
    public function buildModul($_modul)
    {
        // NOTE: it's a shame, that jsb2bin does not do the path rewrite!
        //       as it interprets the jsb2 file it would be the ultimate instance to do so!
        `java -jar {$this->_jsb2bin} --projectFile {$_modul->jsb2file} --homeDir {$this->getHomeDir()}`;
    }
    
    public function adoptPath()
    {
        
    }
    
    /**
     * build package 
     * 
     * NOTE: In fact the build process is on modul/jsb2file basis and not on package basis!
     * 
     * @param  bool $_requiredOnly build package if not exist or ctime of included files is later than ctime of package
     * @return array builded moduls
     */
    protected function _buildPackages($_requiredOnly = FALSE)
    {
        // fetch a _copy_ of the package array
        $pkgs = $this->_registeredPkgs;
        
        // filter out packages not needed to be build
        if ($_requiredOnly) {
            foreach (array('css', 'js') as $what) {
                foreach($pkgs[$what] as $pgkIdx => $pkg) {
                    $pkgFile = $this->getPackageFile($pkg);
                    if (! file_exists($pkgFile->path)) {
                        continue;
                    }
                    
                    $pkgctime = filectime($pkgFile->path);
                    foreach ($this->getIncludedFiles($pkg) as $incFile) {
                        if (! file_exists($incFile->path)) {
                            throw new Exception("required file {$incFile->path} is missing");
                        }
                        
                        if (filectime($incFile->path) > $pkgctime) {
                            continue 2;
                        }
                    }
                    
                    // if we come here, the package dosn't need to be rebuild
                    unset ($pkgs[$what][$pgkIdx]);
                }
            }
        }
        
        // fetch moduls to build
        $modulesBuilded = array();
        foreach (array('css', 'js') as $what) {
            foreach($pkgs[$what] as $pgkIdx => $pkg) {
                if (! in_array($pkg->modul->jsb2file, $modulesBuilded)) {
                    $this->_buildModul($pkg->modul);
                    $modulesBuilded[] = $pkg->modul->jsb2file;
                }
            }
        }
        
        return $modulesBuilded;
    }
    
}