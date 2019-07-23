<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberSkinType
{
    private $xmlId;
    private $name;

    const SKIN_TYPE_TABLE = 'hl_product_skintype';

    public function __construct(string $skinTypeName)
    {
        if ($skinTypeName) {
            $this->name = $skinTypeName;
            $connection = Application::getConnection();
            $skinType = $connection->query('SELECT * from ' . self::SKIN_TYPE_TABLE . ' where UF_NAME="' . $skinTypeName
                . '" limit 1;')->fetch();
            if (!$skinType) {
                $this->xmlId = md5($skinTypeName);
                $connection->query('insert into ' . self::SKIN_TYPE_TABLE . ' (UF_NAME, UF_XML_ID) values ("'
                    . $skinTypeName . '", "' . $this->xmlId . '")');
            } else {
                $this->xmlId = $skinType["UF_XML_ID"];
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