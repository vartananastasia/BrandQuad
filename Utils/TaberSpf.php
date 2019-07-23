<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberSpf
{
    private $xmlId;
    private $name;

    const SPF_TABLE = 'hl_product_spf';

    public function __construct(string $spfName)
    {
        if ($spfName) {
            $this->name = $spfName;
            $connection = Application::getConnection();
            $spf = $connection->query('SELECT * from ' . self::SPF_TABLE . ' where UF_NAME="' . $spfName
                . '" limit 1;')->fetch();
            if (!$spf) {
                $this->xmlId = md5($spfName);
                $connection->query('insert into ' . self::SPF_TABLE . ' (UF_NAME, UF_XML_ID) values ("'
                    . $spfName . '", "' . $this->xmlId . '")');
            } else {
                $this->xmlId = $spf["UF_XML_ID"];
            }
        } else {
            $this->xmlId = null;
        }
    }

    public function xmlId()
    {
        return $this->xmlId;
    }
}