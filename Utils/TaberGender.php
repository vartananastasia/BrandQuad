<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:20
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberGender
{
    private $xmlId;
    private $name;

    const GENDER_TABLE_NAME = 'hl_product_gender';

    public function __construct(string $genderName)
    {
        $this->name = $genderName;
        $connection = Application::getConnection();
        $gender = $connection->query('SELECT * from ' . self::GENDER_TABLE_NAME . ' where UF_NAME="' . $genderName
            . '" limit 1;')->fetch();
        if (!$gender) {
            $this->xmlId = md5($genderName);
            $connection->query('insert into ' . self::GENDER_TABLE_NAME . ' (UF_NAME, UF_XML_ID) values ("'
                . $genderName . '", "' . $this->xmlId . '")');
        } else {
            $this->xmlId = $gender["UF_XML_ID"];
        }
    }

    public function xmlId()
    {
        return $this->xmlId;
    }

}