<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberCountry
{
    private $xmlId;
    private $name;

    const COUNTRY_TABLE_NAME = 'hl_product_country';

    public function __construct(string $countryName)
    {
        $this->name = $countryName;
        $connection = Application::getConnection();
        $country = $connection->query('SELECT * from ' . self::COUNTRY_TABLE_NAME . ' where UF_NAME="' . $countryName
            . '" limit 1;')->fetch();
        if (!$country) {
            $this->xmlId = md5($countryName);
            $connection->query('insert into ' . self::COUNTRY_TABLE_NAME . ' (UF_NAME, UF_XML_ID) values ("'
                . $countryName . '", "' . $this->xmlId . '")');
        } else {
            $this->xmlId = $country["UF_XML_ID"];
        }
    }

    public function xmlId()
    {
        return $this->xmlId;
    }
}