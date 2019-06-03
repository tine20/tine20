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
 * filesystem preview service interface
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
interface Tinebase_FileSystem_Preview_ServiceInterface
{
    /**
     * Uses the DocumentPreviewService to generate previews (images or pdf files) for a file.
     * The preview style (filetype, size, etc.) can be determined by the configuration.
     *
     * A config can consist of multiple conversion configs with a uniqid key.
     *
     * Types:
       Pdf (pdf, gs), Image (png, jpg, ...), Document (odt, docx, xls, ...)
     *
     * Config:
       [
         "synchronRequest" => boolen,   true: realtime priority, false: retry on failure
         "KEY1" => [
            "fileType" => string,      target filetype can be an image extention or 'pdf'
                                       image options:
            "firstPage" => boolen,     only generate preview for first page of each multi page document
            "x" => int,                image max width
            "y" => int,                image max height
            "color" => string/false    color: Rescale image to fit into x,y fills background and margins with color.
                                              (documents have a transparent background, it will be filled)
                                       false: Rescale image to fit into x,y. Aspect ratio is preserved.
         ],
         "KEY2" => [                 second config
            ...
         ],
         ...
       ]
     *
     * Result:
       [
         "KEY1" => [
           binary data,
           ...
         ],
         "KEY1" => [
           binary data,
           ...
         ],
         ...
       ]
     *
     * @param $filePath
     * @param array $config
     * @return array|bool
     * @throws Tinebase_FileSystem_Preview_BadRequestException
     */
    public function getPreviewsForFile($filePath, array $config);

    /**
     * Uses the DocumentPreviewService to generate previews (images or pdf files) for multiple files of same type.
     * The preview style (filetype, size, etc.) can be determined by the configuration.
     *
     * A config can consist of multiple conversion configs with a uniqid key.
     *
     * Types:
       Pdf (pdf, gs), Image (png, jpg, ...), Document (odt, docx, xls, ...)
     *
     * Config:
       [
         "synchronRequest" => boolen,   true: realtime priority, false: retry on failure
         "KEY1" => [
             "fileType" => string,      target filetype can be an image extention or 'pdf'
                                        pdf option:
             "merge" => boolen          merge files into a single pdf
                                        image options:
             "firstPage" => boolen,     only generate preview for first page of each multi page document
             "x" => int,                image max width
             "y" => int,                image max height
             "color" => string/false    color: Rescale image to fit into x,y fills background and margins with color.
                                            (documents have a transparent background, it will be filled)
                                        false: Rescale image to fit into x,y. Aspect ratio is preserved.
            ],
            "KEY2" => [                 second config
                ...
            ],
             ...
       ]
     *
     * Result:
       [
         "KEY1" => [
             binary data,
             ...
         ],
         "KEY1" => [
             binary data,
             ...
         ],
         ...
       ]
     *
     * @param $filePaths array of file Paths to convert
     * @param array $config
     * @return array|bool
     * @throws Tinebase_FileSystem_Preview_BadRequestException
     */
    public function getPreviewsForFiles(array $filePaths, array $config);

    /**
     * Uses the DocumentPreviewService to generate pdfs for a documentfile.
     *
     * @param $filePath
     * @param $synchronRequest bool should the request be prioritized
     * @param array $intermediateFormats
     * @return string file blob
     * @Throws Tinebase_FileSystem_Preview_BadRequestException
     * @throws Tinebase_Exception_UnexpectedValue preview service did not succeed
    */
    public function getPdfForFile($filePath, $synchronRequest = false, $intermediateFormats = []);

    /**
     * Merges multiple pdf files into a single one.
     *
     * @param $filePaths array of file paths
     * @param $synchronousRequest
     * @return string path to file
     * @throws Tinebase_Exception_UnexpectedValue preview service did not succeed
     */
    public function mergePdfFiles($filePaths, $synchronousRequest = false);
}


