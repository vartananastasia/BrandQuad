<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:20
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberGroupHash
{
    private $xmlId;
    private $name;

    const GROUP_TABLE_NAME = 'hl_group_hash';

    public function __construct(string $groupName)
    {
        $this->name = $groupName;
        $connection = Application::getConnection();
        $group = $connection->query('SELECT * from ' . self::GROUP_TABLE_NAME . ' where UF_NAME="' . $groupName
            . '" limit 1;')->fetch();
        if (!$group) {
            $this->xmlId = $groupName;
            $connection->query('insert into ' . self::GROUP_TABLE_NAME . ' (UF_NAME, UF_XML_ID) values ("'
                . $groupName . '", "' . $this->xmlId . '")');
        } else {
            $this->xmlId = $group["UF_XML_ID"];
        }
    }

    public function xmlId()
    {
        return $this->xmlId;
    }

}