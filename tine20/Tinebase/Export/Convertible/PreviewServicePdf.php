<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Michael Spahn <m.spahn@metaways.de>
 * @copyright    Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Trait Tinebase_Export_Convertible_PreviewServicePdf
 */
trait Tinebase_Export_Convertible_PreviewServicePdf
{
    /**
     * @param $from
     * @return string
     * @throws Tinebase_Exception_NotFound
     */
    public function convertToPdf($from)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Converting from ' . $from . ' to PDF');

        $suffixed = $from . '.' . $this->getFormat();
        copy($from, $suffixed);

        $previewService = Tinebase_Core::getPreviewService();
        if (false === ($result = $previewService->getPreviewsForFile($suffixed, ['synchronRequest' => true, ['fileType' => 'pdf',]]))) {
            Tinebase_Core::getLogger()->err(__METHOD__ . ' ' . __LINE__ .
                ' preview service did not succeed');
            throw new Tinebase_Exception_UnexpectedValue('preview service did not succeed');
        }

        file_put_contents($suffixed, $result[0][0]);
        
        $name = $suffixed . '.pdf';
        rename($suffixed, $name);
        
        return $name;
    }
}
