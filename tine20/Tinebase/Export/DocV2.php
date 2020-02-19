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
 *
 * TODO improve templates parts. record / recordrow should have a single template, that can be cached
 * TODO and executed multiple times
 */

class Tinebase_Export_DocV2 extends Tinebase_Export_Doc
{
    protected $_twigSource = null;

    protected function _onBeforeExportRecords()
    {
        parent::_onBeforeExportRecords();

        $this->_twigSource = null;
    }

    protected function _extendTwigSetup()
    {
        parent::_extendTwigSetup();

        // TODO turn on cache, see class comment / todo
        //$this->_twig->getEnvironment()->setCache(Tinebase_Core::getTempDir());
        $this->_twig->getEnvironment()->getExtension(Twig_Extension_Escaper::class)->setDefaultStrategy('html');
    }

    /**
     * @param Tinebase_Record_Interface|null $_record
     */
    protected function _renderTwigTemplate($_record = null)
    {
        // mean hack... this gets called after processing of records is done
        $t = $this;
        $this->_docTemplate->forEachDocument(function(&$xml) use($t) {
            static $a = 0;
            $a += 1;
            $t->_twigSource = $xml;
            $xml = str_replace(["\n", "\r", '\'', Tinebase_Export_Richtext_TemplateProcessor::NEW_LINE_PLACEHOLDER],
            ['</w:t><w:br/><w:t>', '', '&apos;', '</w:t><w:br/><w:t>'],
                // the uniqid is a cache bust
                $this->_twig->load($t->_templateFileName . '#~#' . $t->_docTemplate->getTwigName() . uniqid($a))
                    ->render($t->_getTwigContext(['record' => null])));
        });
    }

    /**
     * @param Tinebase_Record_Interface $_record
     */
    protected function _processRecord(Tinebase_Record_Interface $_record)
    {
        static $a = 0;
        $a += 1;
        $this->_currentProcessor->setMainPart(str_replace(["\n", "\r", '\''], ['</w:t><w:br/><w:t>', '', '&apos;'],
            // the uniqid is a cache bust
            $this->_twig->load($this->_templateFileName . '#~#' . $this->_currentProcessor->getTwigName() . uniqid($a))
            ->render($this->_getTwigContext(['record' => $_record]))));
    }

    public function _getTwigSource()
    {
        if (null === $this->_currentProcessor) {
            // get called in parent::_loadTwig()
            return '';
        }

        if (null !== $this->_twigSource) {
            $src = $this->_twigSource;
        } elseif ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD ||
                $this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_SUBRECORD) {
            $src = '';
            if ($this->_rowCount > 1 && $this->_currentProcessor->hasConfig('separator')) {
                $src .= $this->_currentProcessor->getConfig('separator');
            }
            if ($this->_currentProcessor->hasConfig('header')) {
                $src .= $this->_currentProcessor->getConfig('header');
            }
            $src .= $this->_currentProcessor->getConfig('recordXml');
            if ($this->_currentProcessor->hasConfig('footer')) {
                $src .= $this->_currentProcessor->getConfig('footer');
            }
        } else {
            $src = $this->_currentProcessor->getMainPart();
        }

        return str_replace(["\n", "\r", '&apos;'], ['', '', '\''], $src);
    }

    protected function _startRow()
    {
        if (true === $this->_skip) {
            return;
        }

        $this->_rowCount += 1;

        if ($this->_currentProcessor->hasConfig('recordRow')) {
            $recordRow = $this->_currentProcessor->getConfig('recordRow');
            $this->_currentProcessor->setValue('${RECORD_ROW}', $recordRow['recordRow'] . '${RECORD_ROW}');
        } else {
            if ($this->_currentProcessor->hasConfig('record')) {
                $this->_currentProcessor = $this->_currentProcessor->getConfig('record');
            }

            if ($this->_currentProcessor->getType() !== Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD &&
                $this->_currentProcessor->getType() !== Tinebase_Export_Richtext_TemplateProcessor::TYPE_SUBRECORD) {
                throw new Tinebase_Exception_UnexpectedValue('template and definition do not match');
            }
        }
    }

    protected function _endRow()
    {
        if (true === $this->_skip) {
            return;
        }

        // TODO will it work?
        if ($this->_currentProcessor->hasConfig('subgroups')) {
            foreach ($this->_currentProcessor->getConfig('subgroups') as $property => $group) {
                $this->_executeSubTemplate($property, $group);
            }
        }

        /*{ TODO do subrecords work? has this something to do with this?
            $name = '${R' . $this->_currentProcessor->getConfig('name') . '}';
            $data .= $this->_currentProcessor->getConfig('recordXml') . $name;
            if (($parent = $this->_currentProcessor->getParent()) && $parent->getType() ===
                Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
                $parent = $parent->getParent();
            }
            $parent->setValue($name, $data);
        }*/
        if ($this->_currentProcessor->hasConfig('subrecords')) {
            foreach ($this->_currentProcessor->getConfig('subrecords') as $property => $record) {
                $this->_executeSubTemplate($property, $record);
            }
        }

        if ($this->_currentProcessor->getType() === Tinebase_Export_Richtext_TemplateProcessor::TYPE_RECORD) {
            $this->_currentProcessor->getParent()->setValue('${RECORD_BLOCK}',
                $this->_currentProcessor->getMainPart() . '${RECORD_BLOCK}');
        }
    }
}
