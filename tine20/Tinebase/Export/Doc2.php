<?php
/**
 * Tinebase Doc/Docx generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Tinebase Doc/Docx generation class
 *
 * @package     Tinebase
 * @subpackage  Export
 */

class Tinebase_Export_Doc2 extends Tinebase_Export_Doc
{
    protected function _extendTwigSetup()
    {
        parent::_extendTwigSetup();

        $this->_twig->getEnvironment()->setCache(Tinebase_Core::getTempDir());
        $this->_twig->getEnvironment()->getExtension(Twig_Extension_Escaper::class)->setDefaultStrategy('html');
    }

    /**
     * @param Tinebase_Record_Interface $_record
     */
    protected function _processRecord(Tinebase_Record_Interface $_record)
    {
        $this->_currentProcessor->setMainPart(str_replace(["\n", "\r", '\'',
            Tinebase_Export_Richtext_TemplateProcessor::NEW_LINE_PLACEHOLDER],
            ['</w:t><w:br/><w:t>', '', '&apos;', '</w:t><w:br/><w:t>'],
            $this->_twig->load($this->_templateFileName . '#~#' . $this->_currentProcessor->getTwigName())
            ->render($this->_getTwigContext(['record' => $_record]))));
    }

    public function _getTwigSource()
    {
        if (null === $this->_currentProcessor) {
            // get called in parent::_loadTwig()
            $this->_onBeforeExportRecords();
            return '';
        }

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
            $src = $this->_currentProcessor->getConfig('recordXml');
        } else {
            $src = $this->_currentProcessor->getMainPart();
        }

        while (preg_match('/\{\{[^\}]+\([^\}\)]+=&gt;/', $src, $m)) {
            $src = str_replace($m[0], substr($m[0], 0, strlen($m[0]) - 4) . '>', $src);
        }
        while (preg_match('/\{\{[^\}]+\([^\}\)]+=>[^\)]*&quot;/', $src, $m)) {
            $src = str_replace($m[0], substr($m[0], 0, strlen($m[0]) - 6 /*? oder 5*/) . '"', $src);
        }

        return str_replace(["\n", "\r", '&apos;'], ['', '', '\''], $src);
    }

    protected function _startRow()
    {
        if (true === $this->_skip) {
            return;
        }

        if ($this->_currentProcessor->hasConfig('record')) {
            $this->_currentProcessor = $this->_currentProcessor->getConfig('record');
        }
    }

    protected function _endRow()
    {
        if (true === $this->_skip) {
            return;
        }

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
            $data = $this->_currentProcessor->getMainPart();
            if ($this->_currentProcessor->hasConfig('footer')) {
                $data .= $this->_currentProcessor->getConfig('footer');
            }
            $this->_currentProcessor->getParent()->setValue('${RECORD_BLOCK}', $data . '${RECORD_BLOCK}');
        }
    }
}
