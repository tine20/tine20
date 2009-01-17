<?php
/**
 * Tine 2.0
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 * @license     http://framework.zend.com/license/new-bsd     New BSD License
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * create opendocument files
 *
 * @package     OpenDocument
 * @subpackage  OpenDocument
 */
 
class OpenDocument_Document
{
    const NS_TABLE  = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    const NS_STYLE  = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
    const NS_OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    const NS_FO     = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
    
    protected $_rowStyles = array();
    
    protected $_columnStyles = array();
    
    protected $_cellStyles = array();
    
    protected $_document;
    
    protected $_body;
    
    protected $_content = '<?xml version="1.0" encoding="UTF-8"?>
        <office:document-content 
            xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
            xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" 
            xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" 
            xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" 
            xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" 
            xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" 
            xmlns:xlink="http://www.w3.org/1999/xlink" 
            xmlns:dc="http://purl.org/dc/elements/1.1/" 
            xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" 
            xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" 
            xmlns:presentation="urn:oasis:names:tc:opendocument:xmlns:presentation:1.0" 
            xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" 
            xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" 
            xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" 
            xmlns:math="http://www.w3.org/1998/Math/MathML" 
            xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" 
            xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" 
            xmlns:ooo="http://openoffice.org/2004/office" 
            xmlns:ooow="http://openoffice.org/2004/writer" 
            xmlns:oooc="http://openoffice.org/2004/calc" 
            xmlns:dom="http://www.w3.org/2001/xml-events" 
            xmlns:xforms="http://www.w3.org/2002/xforms" 
            xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
            xmlns:field="urn:openoffice:names:experimental:ooxml-odf-interop:xmlns:field:1.0" office:version="1.1">
            <office:scripts/>
            <office:font-face-decls>
                <style:font-face style:name="Arial" svg:font-family="Arial" style:font-family-generic="swiss" style:font-pitch="variable"/>
                <style:font-face style:name="DejaVu Sans" svg:font-family="&apos;DejaVu Sans&apos;" style:font-family-generic="system" style:font-pitch="variable"/>
            </office:font-face-decls>
            <office:automatic-styles>
                <number:date-style style:name="nShortDate" number:automatic-order="true">
                    <number:day number:style="long"/>
                    <number:text>.</number:text>
                    <number:month number:style="long"/>
                    <number:text>.</number:text>
                    <number:year number:style="long"/>
                </number:date-style>
            </office:automatic-styles>
            <office:body>
                <office:spreadsheet></office:spreadsheet>
            </office:body>
        </office:document-content>';
    
    protected $_manifest = '<?xml version="1.0" encoding="UTF-8"?>
        <manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0">
            <manifest:file-entry manifest:media-type="application/vnd.oasis.opendocument.spreadsheet" manifest:full-path="/"/>
            <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="content.xml"/>
            <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="styles.xml"/>
            <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="meta.xml"/>
            <manifest:file-entry manifest:media-type="text/xml" manifest:full-path="settings.xml"/>
        </manifest:manifest>';
    
    protected $_meta = '<?xml version="1.0" encoding="UTF-8"?>
        <office:document-meta 
            xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
            xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" 
            xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" 
            xmlns:ooo="http://openoffice.org/2004/office" office:version="1.1">
            <office:meta>
                <meta:generator>Tine 2.0</meta:generator>
                <meta:creation-date>2009-01-17T22:04:51</meta:creation-date>
                <meta:editing-cycles>1</meta:editing-cycles>
                <meta:editing-duration>PT0S</meta:editing-duration>
                <meta:user-defined meta:name="Info 1"/>
                <meta:user-defined meta:name="Info 2"/>
                <meta:user-defined meta:name="Info 3"/>
                <meta:user-defined meta:name="Info 4"/>
                <meta:document-statistic meta:table-count="1"/>
            </office:meta></office:document-meta>';
    
    protected $_settings = '<?xml version="1.0" encoding="UTF-8"?>
        <office:document-settings 
            xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
            xmlns:xlink="http://www.w3.org/1999/xlink" 
            xmlns:config="urn:oasis:names:tc:opendocument:xmlns:config:1.0" 
            xmlns:ooo="http://openoffice.org/2004/office" office:version="1.1">
            <office:settings>
            </office:settings>
        </office:document-settings>';
 
    protected $_styles = '<?xml version="1.0" encoding="UTF-8"?>
        <office:document-styles 
            xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
            xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" 
            xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" 
            xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" 
            xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" 
            xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" 
            xmlns:xlink="http://www.w3.org/1999/xlink" 
            xmlns:dc="http://purl.org/dc/elements/1.1/" 
            xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" 
            xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" 
            xmlns:presentation="urn:oasis:names:tc:opendocument:xmlns:presentation:1.0" 
            xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" 
            xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" 
            xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" 
            xmlns:math="http://www.w3.org/1998/Math/MathML" 
            xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" 
            xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" 
            xmlns:ooo="http://openoffice.org/2004/office" 
            xmlns:ooow="http://openoffice.org/2004/writer" 
            xmlns:oooc="http://openoffice.org/2004/calc" 
            xmlns:dom="http://www.w3.org/2001/xml-events" office:version="1.1">
        </office:document-styles>';
    
    public function __construct($_type, $_fileName = null)
    {
        $this->_document = new SimpleXMLElement($this->_content);
        
        $body = $this->_document->xpath('//office:body');
        $node = $body[0]->addChild('office:spreadsheet', NULL, OpenDocument_Document::NS_OFFICE);
        $this->_body = new OpenDocument_SpreadSheet($node);        
    }    
    
    public function getBody()
    {
        return $this->_body;
    }

    public function setRowStyle($_styleName, $_key, $_value)
    {
        $this->_rowStyles[$_styleName][$_key] = $_value;
    }
    
    public function setColumnStyle($_styleName, $_key, $_value)
    {
        $this->_columnStyles[$_styleName][$_key] = $_value;
    }
    
    public function setCellStyle($_styleName, $_nameSpace, $_key, $_value)
    {
        $this->_cellStyles[$_styleName][$_nameSpace][$_key] = $_value;
    }
    
    public function getDocument()
    {
        $this->_body->generateXML();
        
        $this->_addStyles();
        
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $spreadSheat[0]->saveXML());
        
        $filename = '/tmp' . DIRECTORY_SEPARATOR . md5(uniqid(rand(), true)) . '.ods';
            
        if(class_exists('ZipArchive', false)) {
            $zip = new ZipArchive();
            
            if ($zip->open($filename, ZIPARCHIVE::CREATE) !== true) {
                exit("cannot open <$filename>\n");
            }
            
            $zip->addFromString('content.xml', $this->_document->saveXML());
            $zip->addFromString('mimetype', $this->_body->getContentType());
            $zip->addFromString('meta.xml', $this->_meta);
            $zip->addFromString('styles.xml', $this->_styles);
            $zip->addFromString('settings.xml', $this->_settings);
            $zip->addFromString('META-INF/manifest.xml', $this->_manifest);
            
            $zip->close();
        } else {
            $tmp = '/tmp';
            $uid = uniqid();
            mkdir($tmp.'/'.$uid);
            file_put_contents($tmp.'/'.$uid.'/content.xml', $this->_document->saveXML());
            file_put_contents($tmp.'/'.$uid.'/mimetype', $this->_body->getContentType());
            file_put_contents($tmp.'/'.$uid.'/meta.xml', $this->_meta);
            file_put_contents($tmp.'/'.$uid.'/styles.xml', $this->_styles);
            file_put_contents($tmp.'/'.$uid.'/settings.xml', $this->_settings);
            mkdir($tmp.'/'.$uid.'/META-INF/');
            file_put_contents($tmp.'/'.$uid.'/META-INF/manifest.xml', $this->_manifest);
            shell_exec('cd '.$tmp.'/'.$uid.';zip -r '.escapeshellarg($filename).' ./');
            shell_exec('rm -rf '.$tmp.'/'.$uid);
        }
        
        return $filename;
    }
    
    protected function _addStyles()
    {
        $styles = $this->_document->xpath('//office:automatic-styles');
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($styles, true));
        foreach($this->_rowStyles as $key => $rowStyles) {
            $style = $styles[0]->addChild('style:style', NULL, OpenDocument_Document::NS_STYLE);
            $style->addAttribute('style:name', $key, OpenDocument_Document::NS_STYLE);    
            $style->addAttribute('style:family', 'table-row', OpenDocument_Document::NS_STYLE);   

            $property = $style->addChild('style:table-row-properties', NULL, OpenDocument_Document::NS_STYLE);
            foreach($rowStyles as $styleName => $styleValue) {
                $property->addAttribute($styleName, $styleValue, OpenDocument_Document::NS_FO);
            }
        }
        
        foreach($this->_columnStyles as $key => $columnStyles) {
            $style = $styles[0]->addChild('style:style', NULL, OpenDocument_Document::NS_STYLE);
            $style->addAttribute('style:name', $key, OpenDocument_Document::NS_STYLE);    
            $style->addAttribute('style:family', 'table-column', OpenDocument_Document::NS_STYLE);   

            $property = $style->addChild('style:table-column-properties', NULL, OpenDocument_Document::NS_STYLE);
            foreach($columnStyles as $styleName => $styleValue) {
                $property->addAttribute($styleName, $styleValue, OpenDocument_Document::NS_STYLE);
            }
        }
        
        foreach($this->_cellStyles as $key => $cellStyles) {
            $style = $styles[0]->addChild('style:style', NULL, OpenDocument_Document::NS_STYLE);
            $style->addAttribute('style:name', $key, OpenDocument_Document::NS_STYLE);    
            $style->addAttribute('style:family', 'table-cell', OpenDocument_Document::NS_STYLE);   

            #$property = $style->addChild('table-column-properties', NULL, OpenDocument_Document::NS_STYLE);
            foreach($cellStyles as $nameSpace => $styleData) {
                foreach($styleData as $styleName => $styleValue) {
                    $style->addAttribute($styleName, $styleValue, $nameSpace);
                }
            }
        }
    }
}