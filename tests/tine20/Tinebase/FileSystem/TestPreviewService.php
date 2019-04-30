<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem preview service test implementation
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_TestPreviewService implements Tinebase_FileSystem_Preview_ServiceInterface
{
    protected $returnValueGetPreviewsForFile = array('thumbnail' => array('blob'), 'previews' => array('blob1', 'blob2', 'blob3'));
    protected $returnValueGetPreviewsForFiles = array('thumbnail' => array('blob'), 'previews' => array('blob1', 'blob2', 'blob3'));
    protected $returnValueGetPdfForFile = "%PDF-1.0"; //the mimetype is correct, but it not a valid pdf

    /**
     * @var Exception
     */
    protected $throwExceptionGetPreviewsForFile = null;

    /**
     * @var Exception
     */
    protected $throwExceptionGetPreviewsForFiles = null;


    public function getPreviewsForFile($_filePath, array $_config)
    {
        if ($this->throwExceptionGetPreviewsForFile != null) {
            throw $this->throwExceptionGetPreviewsForFile;
        }

        return $this->returnValueGetPreviewsForFile;
    }

    /**
     * Generates previews for files of same type(1)
     * Files (2) will be merged into a single pdf, unless merge = false is set in $config
     *
     * (1) Types: Pdf (pdf, gs), Image (png, jpg, ...), Document (odt, docx, xls, ...)
     * (2) with target Pdf or Image
     *
     * @param $filePaths array of file Paths to convert
     * @param array $config
     * @return array|bool
     */
    public function getPreviewsForFiles(array $filePaths, array $config)
    {
        if ($this->throwExceptionGetPreviewsForFiles != null) {
            throw $this->throwExceptionGetPreviewsForFiles;
        }

        return $this->returnValueGetPreviewsForFiles;
    }


    /**
     * Uses the DocumentPreviewService to generate pdfs for a documentfile.
     *
     * @param $filePath
     * @param $synchronRequest bool should the request be prioritized
     * @return string file blob
     * @throws Tinebase_Exception_UnexpectedValue preview service did not succeed
     */
    public function getPdfForFile($filePath, $synchronRequest = false, $intermediateFormats = [])
    {
        return $this->returnValueGetPdfForFile;
    }



    public function setReturnValueGetPreviewsForFile($value)
    {
        $this->returnValueGetPreviewsForFile = $value;
    }

    public function setReturnValueGetPreviewsForFiles($value)
    {
        $this->returnValueGetPreviewsForFiles = $value;
    }

    public function setReturnValueGetPdfForFile($value)
    {
        $this->returnValueGetPdfForFile = $value;
    }

    public function setThrowExceptionGetPreviewsForFile($throwExceptionGetPreviewsForFile)
    {
        $this->throwExceptionGetPreviewsForFile = $throwExceptionGetPreviewsForFile;
    }

    public function setThrowExceptionGetPreviewsForFiles($throwExceptionGetPreviewsForFiles)
    {

    }

    /**
     * Merges multiple pdf files into a single one.
     *
     * @param $filePaths array of file paths
     * @param $synchronousRequest
     * @return string path to file
     */
    public function mergePdfFiles($filePaths, $synchronousRequest = false)
    {
        return "blob";
    }
}