<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberAreaOfUse
{
    private $xmlId;
    private $name;

    const AREAOFUSE_TABLE = 'hl_product_areaofuse';

    public function __construct(string $areaOfUseName)
    {
        if ($areaOfUseName) {
            $this->name = $areaOfUseName;
            $connection = Application::getConnection();
            $areaOfUse = $connection->query('SELECT * from ' . self::AREAOFUSE_TABLE . ' where UF_NAME="' . $areaOfUseName
                . '" limit 1;')->fetch();
            if (!$areaOfUse) {
                $this->xmlId = md5($areaOfUseName);
                $connection->query('insert into ' . self::AREAOFUSE_TABLE . ' (UF_NAME, UF_XML_ID) values ("'
                    . $areaOfUseName . '", "' . $this->xmlId . '")');
            } else {
                $this->xmlId = $areaOfUse["UF_XML_ID"];
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