<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Trait Tinebase_Export_Convertible_PreviewServicePdf
 */
trait Tinebase_Export_Convertible_PreviewServicePdf
{
    /**
     * @param $from
     * @param array $intermediateFormats check if the docService supports this transformation before use
     * @return string
     */
    public function convertToPdf($from, $intermediateFormats = [])
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Converting from ' . $from . ' to PDF');
        }

        $suffixed = $from . '.' . $this->getFormat();
        copy($from, $suffixed);

        $result = Tinebase_Core::getPreviewService()->getPdfForFile($suffixed, true, $intermediateFormats);

        file_put_contents($suffixed, $result);
        
        $name = $suffixed . '.pdf';
        rename($suffixed, $name);
        
        return $name;
    }
}
