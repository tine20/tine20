<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

trait Tinebase_Export_DocumentPdfTrait
{

    public static $previewService = null;

    /**
     * @var null|string
     */
    protected $_parentFile;

    /**
     * @var null|Tinebase_FileSystem_Preview_ServiceInterface
     */
    protected $_previewService = null;
    
    protected $_useOO = false;

    /**
     * @return string
     */
    protected abstract function _getOldFormat();

    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     */
    public function __construct(
        Tinebase_Model_Filter_FilterGroup $_filter = null,
        Tinebase_Controller_Record_Interface $_controller = null,
        array $_additionalOptions = [],
        $_previewService = null
    ) {
        $this->_previewService = $_previewService ?: (static::$previewService ?: Tinebase_Core::getPreviewService());

        $this->_format = 'pdf';

        if ($this->_useOO && (!class_exists('OnlyOfficeIntegrator_Config') ||
                !Tinebase_Application::getInstance()->isInstalled(OnlyOfficeIntegrator_Config::APP_NAME))) {
            $this->_useOO = false;
        }

        parent::__construct($_filter, $_controller, $_additionalOptions);
    }

    /**
     * output result
     *
     * @param string $_target
     * @return string result
     */
    public function write($_target = null)
    {
        $tempfile = Tinebase_TempFile::getTempPath() . '.' . $this->_getOldFormat();
        if ($this instanceof Tinebase_Export_Xls) {
            // @phpstan-ignore-next-line
            parent::write($tempfile);
        } else {
            parent::save($tempfile);
        }

        $this->_parentFile = $tempfile;

        if (!$this->_useOO || !class_exists('OnlyOfficeIntegrator_Frontend_Json') || !class_exists('OnlyOfficeIntegrator_Controller')) {
            $previewUrl = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL};
            if (!empty($previewUrl)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . ' Creating PDF on preview service (url: ' . $previewUrl . ')');
                }

                $result = $this->_previewService->getPdfForFile($tempfile, true);

            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . ' Preview service disabled / not configured');
                }
                throw new Tinebase_Exception_Backend('preview service not configured');
            }
        } else {
            $tempfile = Tinebase_TempFile::getInstance()->createTempFile($tempfile, 'export.' . $this->_getOldFormat());
            $editorCfg = (new OnlyOfficeIntegrator_Frontend_Json())->getEditorConfigForTempFileId($tempfile->getId());
            $result = OnlyOfficeIntegrator_Controller::getInstance()->callConversionService([
                'async' => false,
                'filetype' => $this->_getOldFormat(),
                'outputtype' => 'pdf',
                'title' => 'Example Document Title',
                'url' => $editorCfg['document']['url'],
                'key' => $editorCfg['document']['key'],
            ]);

            if (!isset($result['fileUrl'])) {
                throw new Exception('bla');
            }
            $result = file_get_contents($result['fileUrl']);
        }

        if (null !== $_target) {
            file_put_contents($_target, $result);
        } else {
            // outputs pdf!
            echo $result;
        }

        return $result;
    }

    public function save($filename)
    {
        $this->write($filename);
        return $filename;
    }


    /**
     * get download content type
     *
     * @return string
     */
    public function getDownloadContentType()
    {
        return 'application/x-pdf';
    }

    /**
     * return download filename
     * @param string $_appName
     * @param string $_format
     * @return string
     */
    public function getDownloadFilename($_appName = null, $_format = null)
    {
        return parent::getDownloadFilename($_appName, $_format);
    }
}
