<?php
/**
 * Application Generator
 *
 * @package     Tool
 * @subpackage  CodeGenerator
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
class Tool_CodeGenerator_Application implements Tool_CodeGenerator_Interface
{
    const APPLICATION_NAME = '[APPLICATION_NAME]';
    const DS = DIRECTORY_SEPARATOR;
    
    public static $_resultMessage = null;

    /**
     * List of source and target files
     * @var array
     */
    protected $_sourceAndTargets = array();

    /**
     * List of files to be created
     * @var array
    */
    protected $_folders = array(
            'Backend',
            'Controller',
            'css',
            'Frontend',
            'js',
            'translations',
            'Model',
            'Setup'
    );

    protected $recursiveSources = array(
            'translations'
    );

    /**
     * 
     * @var application root directory
     */
    protected $_applicationFolder = null;

    /**
     * 
     * @var name of application applied to every class
     */
    protected $_applicationName = null;

    /**
     * 
     * @var reference folder for finding templates 
     */
    protected $_rootFolder = null;
    
    /**
     * 
     * @var absolute path for templates
     */
    protected $_templateFolder = null;

    public function __construct()
    {
        $applicationName = self::APPLICATION_NAME;

        $this->_sourceAndTargets =  array(
            'Backend/ExampleRecord.php' => "Backend/{$applicationName}Record.php",
            'Controller/ExampleRecord.php' => "/Controller/{$applicationName}Record.php",
            'css/ExampleApplication.css' => "/css/{$applicationName}.css",
            'Frontend/Cli.php' => 'Frontend/Cli.php',
            'Frontend/Http.php' => 'Frontend/Http.php',
            'Frontend/Json.php' => 'Frontend/Json.php',
            'js/ExampleRecordDetailsPanel.js' => "js/{$applicationName}RecordDetailsPanel.js",
            'js/ExampleRecordEditDialog.js' => "js/{$applicationName}RecordEditDialog.js",
            'js/ExampleRecordGridPanel.js' => "js/{$applicationName}RecordGridPanel.js",
            'Model/ExampleRecord.php' => "Model/{$applicationName}Record.php",
            'Model/ExampleRecordFilter.php' => "Model/{$applicationName}RecordFilter.php",
            'Model/Status.php' => "Model/Status.php",
            'Setup/Initialize.php' => 'Setup/Initialize.php',
            'Setup/setup.xml' => 'Setup/setup.xml',
            'translations' => 'translations',
            'Config.php' => 'Config.php',
            'Controller.php' => 'Controller.php',
            'ExampleApplication.jsb2' => $applicationName . '.jsb2',
            'Exception.php' => 'Exception.php',
            'Preference.php' => 'Preference.php'
        );
    }

    /**
     * (non-PHPdoc)
     * @see Tool_CodeGenerator_Interface::build()
     */
    public function build(array $args)
    {
        try {
            $this->_applicationName = $args[0];

            // creates application folder
            $this->_applicationFolder = $args[count($args)-1] . self::DS . $args[0];
                    
            $this->_rootFolder = $args[count($args)-1];
            
            $this->_templateFolder = realpath($this->_rootFolder . self::DS . 'ExampleApplication');
                    
            $this->_createFolders();

            $this->_copyFiles();
            
            return "Application {$this->_applicationName} was created successfully into tine20 folder! \n";
                    
        } catch (Exception $e) {
            return self::$_resultMessage = $e->getMessage();
        }

    }

    /**
     * Creates application folders
     */
    protected function _createFolders()
    {
        mkdir($this->_fsOsSintax($this->_applicationFolder));

        foreach ($this->_folders as $folder)
        {
            mkdir($this->_fsOsSintax($this->_applicationFolder . self::DS . $folder));
        }
    }

    /**
     * Copy template files to target folders
     */
    protected function _copyFiles()
    {
        foreach($this->_sourceAndTargets as $source => $target) {
            $target = str_replace(self::APPLICATION_NAME, $this->_applicationName, $target);

            if (in_array($target, $this->recursiveSources)) {                
                $directory = scandir($this->_fsOsSintax($this->_templateFolder . self::DS . $source));
                unset($directory[0]); // this directory
                unset($directory[1]); // parent directory
                
                foreach($directory as $file) {
                    $sourcePath = $this->_templateFolder . self::DS . $source . self::DS . $file;
                    $targetPath = $this->_applicationFolder . self::DS . $target . self::DS . $file;
                    $this->_copyFile($sourcePath, $targetPath);
                }
                
            } else {
                $sourcePath = $this->_templateFolder . self::DS . $source;
                $targetPath = $this->_applicationFolder . self::DS . $target;
                $this->_copyFile($this->_templateFolder . self::DS . $source, $targetPath);
            }
        }
    }

    /**
     * Copy file $sourcePath to $targetPath
     * @param string $sourcePath
     * @param string $targetPath
     */
    protected function _copyFile($sourcePath, $targetPath)
    {
        copy($this->_fsOsSintax($sourcePath), $this->_fsOsSintax($targetPath));
        $this->_changeFile($targetPath);
    }

    /**
     * Change content of copied files
     * @param string $targetPath
     */
    protected function _changeFile($targetPath)
    {
        $content = file_get_contents($this->_fsOsSintax($targetPath));

        $content = str_replace('ExampleApplication', $this->_applicationName, $content);
        $content = str_replace('ExampleRecord', $this->_applicationName . 'Record', $content);
        $content = str_replace('EXAMPLERECORD', strtoupper($this->_applicationName) . 'RECORD', $content);
        $chainFilter = new Zend_Filter();
        $chainFilter->addFilter(new Zend_Filter_Word_CamelCaseToUnderscore())
                    ->addFilter(new Zend_Filter_StringToLower());
        $content = str_replace('example_application_record', $chainFilter->filter($this->_applicationName) . '_record', $content);        

        file_put_contents($targetPath, $content);
    }
    
    /**
     * Ensures that path is according to sintax of filesystem
     * Try to use only in PHP core functions
     * @param string $path
     * @return string
     */
    protected function _fsOsSintax($path)
    {
        return str_replace('/', self::DS, $path);
    }  
}
