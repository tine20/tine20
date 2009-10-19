<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Tables.php 1063 2008-03-14 16:28:12Z lkneschke $
 *
 * @todo rework ext check classes!
 */

/*
* checks local PHP installation for needed configuration details defined in essentials.xml
*/


/*
* Output factory
*/

interface TableFactory
{
    public function createTable();
    public function createRow();
    public function createHeader();
    public function createCell($content, $color = NULL);
}

/**
* abstract output classes
*/
abstract class Table
{
    protected $header = NULL;
    protected $rows = array();

    public function setHeader(Header $header)
    {
        $this->header = $header;
    }

    public function addRow(Row $row)
    {
        $this->rows[] = $row;
    }

    abstract public function display();
}

abstract class Row
{
    protected $cells = array();

    public function addCell(Cell $cell)
    {
        $this->cells[] = $cell;
    }

    abstract public function display();
}

abstract class Header extends Row {}


abstract class Cell
{
    protected $content = NULL;
    protected $color = NULL;

    public function __construct($content, $color = NULL)
    {
        $this->content = $content;
        $this->color = $color;
    }

    abstract public function display();
}

/*
Text Output
*/

class TextCell extends Cell
{
    public function display()
    {
        echo "|" . str_pad($this->content, 20) ;
    }

}

class TextRow extends Row
{
    public function display()
    {
        echo "\n";
        foreach ($this->cells as $cell)
        {
            $cell->display();
        }

        echo "|";
        echo "\n+" . str_repeat("-", (count($this->cells) * 21) -1) . "+";
    }
}

class TextHeader extends Header
{
    public function display()
    {
        echo "+" . str_repeat("-", (count($this->cells) * 21) -1) . "+\n";

        foreach ($this->cells as $cell)
        {
            $cell->display();
        }

        echo "|";
        echo "\n+" . str_repeat("-", (count($this->cells) * 21) -1) . "+";
    }
}

class TextTable extends Table
{
    public function display()
    {
        $this->header->display();

        foreach ($this->rows as $row)
        {
            $row->display();
        }
        echo "\n";

    }

}

class TextTableFactory implements TableFactory
{
    public function createTable()
    {
        $table = new TextTable();
        return $table;
    }

    public function createRow()
    {
        $row = new TextRow();
        return $row;
    }

    public function createHeader()
    {
        $header = new TextHeader();
        return $header;
    }

    public function createCell($content, $color = NULL)
    {
        $cell = new TextCell($content);
        return $cell;
    }

}

/*
HTML Output
*/

class HTMLCell extends Cell
{
    public function display()
    {
        if ($this->color)
        {
            return "\n\t\t\t<td style=\"color:" . $this->color . "\">" . $this->content . "</td>";
        }
        else
        {
            return "\n\t\t\t<td>" . $this->content . "</td>";
        }
    }

}

class HTMLRow extends Row
{
    public function display()
    {
        $buffer =  "\n\t\t<tr>";

        foreach ($this->cells as $cell)
        {
            $buffer .= $cell->display();
        }
        return $buffer . "\n\t\t</tr>";
    }
}

class HTMLHeader extends Header
{
    public function display()
    {
        $buffer = "\n\t\t<tr style=\"font-weight: bold;\">";

        foreach ($this->cells as $cell)
        {
            $buffer .=$cell->display();
        }

        return $buffer .  "\n\t\t</tr>";
    }
}

class HTMLTable extends Table
{
    public function display()
    {
        $buffer = "\n\t<table border>";

        $this->header->display();

        foreach ($this->rows as $row)
        {
            $buffer .= $row->display();
        }

        return $buffer . "\n\t</table>";
    }

}

class HTMLTableFactory implements TableFactory
{
    public function createTable()
    {
        $table = new HtmlTable();
        return $table;
    }

    public function createRow()
    {
        $row = new HTMLRow();
        return $row;
    }

    public function createHeader()
    {
        $header = new HTMLHeader();
        return $header;
    }

    public function createCell($content, $color= NULL)
    {
        $cell = new HTMLCell($content, $color);
        return $cell;
    }

}

/*
* XML parsing
*/

class ExtensionList
{
    protected $tableFactory = NULL;

    public function __construct(TableFactory $tableFactory)
    {
        $this->tableFactory = $tableFactory;
    }

    public function showTable($data)
    {
        $table = $this->tableFactory->createTable();

        $header = $this->tableFactory->createHeader();
        $header->addCell($this->tableFactory->createCell('Item checked'));
        $header->addCell($this->tableFactory->createCell('Status'));

        $table->setHeader($header);

        foreach ($data as $line)
        {
            $row = $this->tableFactory->createRow();
            $table->addRow($row);

            foreach ($line as $field)
            {
                $cell = $this->tableFactory->createCell($field);

                if ($field == 'FAILURE')
                {
                    $color = "#ff0000";
                    $cell = $this->tableFactory->createCell($field, $color);
                }
                else if ($field == 'SUCCESS')
                {
                    $color = "#00ff00";
                    $cell = $this->tableFactory->createCell($field, $color);
                }


                $row->addCell($cell);
            }

        }

        return $table->display();
    }

}


/**
 * ext check class
 * 
 * @package     Setup
 */
class Setup_ExtCheck
{
    /**
     * the constructor
     *
     * @param string $_file
     */
    public function __construct($_file = NULL)
    {
        if (isset($_SERVER['SHELL']) || isset($_SERVER['ProgramFiles'])) {
            // Unix-Shell; Windows-Kommandozeile
            $this->list = new ExtensionList(new TextTableFactory());
        } else {
            $this->list = new ExtensionList(new HTMLTableFactory());
        }

        /*
        * fetch local server info
        */
        $this->loadedExtensions = get_loaded_extensions();

        $this->values = $this->_getConfiguration($_file);
    }
    
    /**
     * php extensions
     *
     * @var array
     */
    private $loadedExtensions = array();

    /**
     * values from extensions xml file
     *
     * @var array
     */
    public $values = array();

    /**
     * output string
     *
     * @var string
     */
    public $output = '';
    
    /**
    * read configuration
    * 
    * @param string $_file xml file with the config options 
    */
    private function _getConfiguration($_file)
    {
        if ($fileHandle = fopen($_file, 'r'))
        {
            $buffer = '';
            while (!feof($fileHandle))
            {
               $buffer .= fgets($fileHandle, 4096);
            }
            fclose($fileHandle);

            $values = array();
            $parser = xml_parser_create();
            xml_parse_into_struct($parser, $buffer, $values);
            xml_parser_free($parser);
        } else {
            throw new Setup_Exception("File $_file not found!");
        }
        return $values;
    }

    /**
     * checks the environment
     *
     * @return array with success/failure values for the given attributes
     * 
     */
    private function _check()
    {
        foreach ($this->values as $key => $value) {
            if ($value['tag'] == 'ENVIROMENT') {
                switch($value['attributes']['NAME']) {
                case 'Zend':
                    $required = $value['attributes']['VERSION'];
                    $zend = Zend_Version::VERSION;
                    $operator = ($value['attributes']['OPERATOR'] == 'biggerThan') ? '>' : '<';
                    $text = $value['attributes']['NAME'] . ' ' . $operator . ' ' . $required;
                    if (version_compare($zend, $required, $operator)) {
                        $data[] = array($text, 'SUCCESS');
                    } else {
                        $data[] = array($text . ' (version is ' . $zend . ')', 'FAILURE');
                    }
                    break;
                case 'PHP':
                    if (version_compare($value['attributes']['VERSION'], phpversion(), '<')) {
                        $data[] = array($value['attributes']['NAME'], 'SUCCESS');
                    } else {
                        $data[] = array($value['attributes']['NAME'], 'FAILURE');
                    }
                    break;
                case 'MySQL':
                    // get setup controller for database connection
                    $dbConfig = Tinebase_Core::getConfig()->database;
                    $hostnameWithPort = (isset($dbConfig->port)) ? $dbConfig->host . ':' . $dbConfig->port : $dbConfig->host;
                    $link = @mysql_connect($hostnameWithPort, $dbConfig->username, $dbConfig->password);
                    if (!$link) {
                        //die('Could not connect to mysql database: ' . mysql_error());
                        
                        Setup_Core::set(Setup_Core::CHECKDB, FALSE);
                    }                    
                    //echo "mysql version: " . mysql_get_server_info();
                    if (version_compare($value['attributes']['VERSION'], @mysql_get_server_info(), '<')) {
                        $data[] = array($value['attributes']['NAME'], 'SUCCESS');
                    } else {
                        $data[] = array($value['attributes']['NAME'], 'FAILURE');
                    }
                    break;
                default:
                    $data[] = array($value['attributes']['NAME'], 'FAILURE');
                    break;
                }
            } else if ($value['tag'] == 'EXTENSION') {

                //print_r($this->loadedExtensions);
                
                foreach ($value as $extensionArray) {
                    if (is_array($extensionArray)) {
                        $succeeded = false;

                        if (in_array($extensionArray['NAME'], $this->loadedExtensions)) {
                            
                            $passed[] = true;

                            if ($this->values[($key + 1)]['tag'] == 'INISET') {
                                $iniSettings = ini_get_all($extensionArray['NAME']);
                                //print_r($iniSettings);
                                $i = 1;
                                while ($values[($key + $i)]['tag'] == 'INISET') {
                                    switch ($values[($key + $i)]['attributes']['OPERATOR']) {
                                        case('<='):
                                            if (!$iniSettings[$values[($key + $i)]['attributes']['NAME']][$values[($key + $i)]['attributes']['SCOPE']] 
                                                    <= $values[($key + $i)]['attributes']['VALUE']) {
                                                $passed[] = false;
                                            }
                                            break;
                                        case('=='):
                                            if (!$iniSettings[$values[($key + $i)]['attributes']['NAME']][$values[($key + $i)]['attributes']['SCOPE']] 
                                                    == $values[($key + $i)]['attributes']['VALUE']) {
                                                $passed[] = false;
                                            }
                                            break;
                                        case('>='):
                                            if (!$iniSettings[$values[($key + $i)]['attributes']['NAME']][$values[($key + $i)]['attributes']['SCOPE']] 
                                                    >= $values[($key + $i)]['attributes']['VALUE']) {
                                                $passed[] = false;
                                            }
                                            break;
                                        default:
                                            break;
                                    }
                                    $i++;
                                }
                            } // end INISET
                            
                            if (!in_array(false, $passed)) {                             
                                $succeeded = true;
                            }
                            unset($passed);
                            unset($iniSettings);
                        }

                        if ($succeeded) {
                            $data[] = array($extensionArray['NAME'], 'SUCCESS');
                        } else {
                            $data[] = array($extensionArray['NAME'], 'FAILURE');
                        }
                    }
                }
            } // end EXTENSION
        } // end foreach
        
        return $data;
    }

    /**
     * get output
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output = $this->list->showTable($this->_check());
    }
    
    /**
     * get check result data
     *
     * @return array
     */
    public function getData()
    {
        $helperLink = ' <a href="http://www.tine20.org/wiki/index.php/Admins/Install_Howto" target="_blank">Check the Tine 2.0 wiki for support.</a>';
        
        $result = array(
            'success'   => TRUE,
            'result'    => array(),
        );
        
        $data = $this->_check();
        
        foreach ($data as $check) {
            list($key, $value) = $check;
            if ($value != 'SUCCESS') {
                if ($key === 'PHP') {
                    $message = 'PHP version too low: ' . phpversion();
                } else {
                    if ($key === 'MySQL') {
                        $message = 'Could not connect to MySQL DB, version incompatible (' . @mysql_get_server_info() . ') or ';
                    } else {
                        $message = '';
                        $result['success'] = FALSE;
                    }
                    $message .= 'Extension ' . $key . ' not found.' . $helperLink;
                }
                
                $result['result'][] = array(
                    'key'       => $key,
                    'value'     => FALSE,
                    'message'   => $message
                );
            } else {
                $result['result'][] = array(
                    'key'   => $key,
                    'value' => TRUE,
                    'message'   => ''
                );
            }
        }
        
        return $result;
    }

    /**
     * get single extension data
     *
     * @param string $_name
     * @return array|boolean
     */
    public function getExtensionData($_name)
    {
        $result = FALSE;
        
        foreach ($this->values as $key => $value) {
            if ($value['tag'] == 'ENVIROMENT') {
                if ($value['attributes']['NAME'] == $_name) {
                    $result = $value['attributes'];
                } 
            }
        }
        
        return $result;
    }
}


