<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Antonio Carlos da Silva <antonio-carlos.silva@serpro.gov.br>
 * @author      Mario Cesar Kolling <mario.kolling@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @todo        support titulo and INSS Data
 * @todo        support pf and app/server certificates data.
 * @todo        treat other OIDs
 * 
 */

class Tinebase_Auth_ModSsl_Certificate_ICPBrasil extends Tinebase_Auth_ModSsl_Certificate_X509
{
    const OID_PF = '2.16.76.1.3.1';
    const OID_PJ_RESPONSAVEL = '2.16.76.1.3.2';
    const OID_PJ_CNPJ = '2.16.76.1.3.3';
    const OID_PJ_DADOS_RESPONSAVEL = '2.16.76.1.3.4';
    const OID_PF_TITULO = '2.16.76.1.3.5';
    const OID_PF_CADINSS = '2.16.76.1.3.6';
    const OID_PJ_CEI = '2.16.76.1.3.7';
    const OID_APP_NOMEEMPRESARIAL = '2.16.76.1.3.8';
    
    /**
     * @var boolean
     */
    protected $pf = false;
    
    /**
     * @var boolean
     */
    protected $pj = false;
    
    /**
     * @var boolean
     */
    protected $app = false;
    protected $cpf = null;
    protected $nascimento = null;
    protected $nis = null;
    protected $rg = null;
    protected $orgaoUF = null;
    
    public function __construct($certificate, $icpBrasilData)
    {
        // parse $oid and get specific info from
        if (array_key_exists(self::OID_PF, $icpBrasilData)) {
            $this->pf = true;
        } else if (array_key_exists(self::OID_PJ_CEI, $icpBrasilData)) {
            $this->pj = true;
        } else if (array_key_exists(self::OID_APP_NOMEEMPRESARIAL, $icpBrasilData)) {
            $this->app = true;
        }
        
        foreach ($icpBrasilData as $oid => $value) {
            // TODO: Treat other OIDs
            if ($oid == self::OID_PF) {
                $this->cpf = $value['CPF'];
                $this->nascimento = $value['NASCIMENTO'];
                $this->nis = $value['NIS'];
                $this->rg = $value['RG'];
                $this->orgaoUF['ORGAOUF'];
            }
        }
        
        parent::__construct($certificate);
    }
    
    /**
    * Parse ICP-BRASIL OIDs...
    *
    * @param $certificate string
    * @return array
    */        
    static public function parseICPBrasilData($certificate)
    {
        //  OID's PESSOA FISICA = 2.16.76.1.3.1 ,  2.16.76.1.3.6 ,  2.16.76.1.3.5
        //  OID's PESSOA JURIDICA = 2.16.76.1.3.4 ,  2.16.76.1.3.2 , 2.16.76.1.3.3 , 2.16.76.1.3.7
        //  OID's EQUIPAMENTO/APLICACAO = 2.16.76.1.3.8 ,  2.16.76.1.3.3 , 2.16.76.1.3.2 , 2.16.76.1.3.4
        $oids = array(
            '2.16.76.1.3.1' => array(
                '1' => array('NASCIMENTO',8),
                '2'=>array('CPF',11),
                '3'=>array('NIS',11),
                '4'=>array('RG',15),
                '5'=>array('ORGAOUF',6)
            ),
            '2.16.76.1.3.2' => array(
                '1'=>array('NOMERESPONSAVELCERTIFICADO',0)
            ),
            '2.16.76.1.3.3' => array(
                '1'=>array('CNPJ',14)
            ),
            '2.16.76.1.3.4' => array(
                '1'=>array('NASCIMENTO',8),
                '2'=>array('CPF',11),
                '3'=>array('NIS',11),
                '4'=>array('RG',15),
                '5'=>array('ORGAOUF',6)
            ),
            '2.16.76.1.3.5' => array(
                '1'=>array('TITULO',12),
                '2'=>array('ZONA',3),
                '3'=>array('SECAO',4),
                '4'=>array('TITULO_CIDADE_UF',0)
            ),
            '2.16.76.1.3.6' => array(
                '1'=>array('CADINSS',12)
            ),
            '2.16.76.1.3.7' => array(
                '1'=>array('CEI',12)
            ),
            '2.16.76.1.3.8' => array(
                '1'=>array('NOMEEMPRESARIAL',0)
            ),
        );

        $result = array();
        $derCertificate = self::pem2Der($certificate);
            
        foreach ($oids as $oid => $thisOid) {

            $hOid = self::oid2Hex($oid);
            $value = '';

            $parts = explode($hOid,$derCertificate);
            if(count($parts) == 1) {
                continue;
            }
            
            $length = ord(substr($parts[1],1,1))-2;
            $value = substr($parts[1],4,$length);

            $p = 0;
            for($i=1;$i < count($thisOid) + 1; $i++) {
                if($thisOid[$i][1] == 0) {
                    // if equals to 0, it's pointig to the last element
                    // begining at $p to the end of the data.
                    $size = strlen($value) - $p;
                } else {
                    $size = $thisOid[$i][1];
                }
                
                $result[$oid][$thisOid[$i][0]] = substr($value,$p,$size);
                $p = $p + $thisOid[$i][1];
            }
        }
        
        if (empty($result)) {
            return FALSE;
        }
        
        return $result;
    }
    
    public function isPF()
    {
        return $this->pf;
    }

    public function isPJ()
    {
        return $this->pj;
    }

    public function isApp()
    {
        return $this->app;
    }
    
    public function getCPF()
    {
        return $this->cpf;
    }
    
    public function getNascimento()
    {
        return $this->nascimento;
    }

    public function getNis()
    {
        return $this->nis;
    }

    public function getRg()
    {
        return $this->rg;
    }

    public function getOrgaoUF()
    {
        return $this->orgaoUF;
    }

}
