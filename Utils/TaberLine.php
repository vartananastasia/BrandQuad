<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 14:35
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberLine
{
    private $xmlId;
    private $name;
    private $brand;

    const LINE_TABLE_NAME = 'hl_product_line';

    public function __construct(string $lineName, string $brandId)
    {
        $this->name = $lineName;
        $this->brand = $brandId;
        $connection = Application::getConnection();
        $line = $connection->query('SELECT * from ' . self::LINE_TABLE_NAME . ' where UF_NAME="' . $lineName
            . '" and UF_BRAND="' . $brandId . '" limit 1;')->fetch();
        if (!$line) {
            $this->xmlId = md5($lineName . $brandId);
            $connection->query('insert into ' . self::LINE_TABLE_NAME . ' (UF_NAME, UF_BRAND, UF_SORT, UF_ACTIVE, UF_XML_ID) values ("'
                . $lineName . '", ' . $brandId . ', 500, "Y", "' . $this->xmlId . '")');
        } else {
            $this->xmlId = $line["UF_XML_ID"];
        }
    }

    public function xmlId()
    {
        return $this->xmlId;
    }
}