<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberAge
{
    private $xmlId;
    private $name;

    const AGE_TABLE = 'hl_product_age';

    public function __construct(string $ageName)
    {
        if ($ageName) {
            $this->name = $ageName;
            $connection = Application::getConnection();
            $age = $connection->query('SELECT * from ' . self::AGE_TABLE . ' where UF_NAME="' . $ageName
                . '" limit 1;')->fetch();
            if (!$age) {
                $this->xmlId = md5($ageName);
                $connection->query('insert into ' . self::AGE_TABLE . ' (UF_NAME, UF_XML_ID) values ("'
                    . $ageName . '", "' . $this->xmlId . '")');
            } else {
                $this->xmlId = $age["UF_XML_ID"];
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