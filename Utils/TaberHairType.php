<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberHairType
{
    private $xmlId;
    private $name;

    const HAIR_TYPE_TABLE = 'hl_product_hair_type';

    public function __construct(string $hairTypeName)
    {
        if ($hairTypeName) {
            $this->name = $hairTypeName;
            $connection = Application::getConnection();
            $hairType = $connection->query('SELECT * from ' . self::HAIR_TYPE_TABLE . ' where UF_NAME="' . $hairTypeName
                . '" limit 1;')->fetch();
            if (!$hairType) {
                $this->xmlId = md5($hairTypeName);
                $connection->query('insert into ' . self::HAIR_TYPE_TABLE . ' (UF_NAME, UF_XML_ID) values ("'
                    . $hairTypeName . '", "' . $this->xmlId . '")');
            } else {
                $this->xmlId = $hairType["UF_XML_ID"];
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