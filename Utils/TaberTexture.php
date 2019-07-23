<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberTexture
{
    private $xmlId;
    private $name;

    const TEXTURE_TABLE = 'hl_product_texture';

    public function __construct(string $textureName)
    {
        if ($textureName) {
            $this->name = $textureName;
            $connection = Application::getConnection();
            $texture = $connection->query('SELECT * from ' . self::TEXTURE_TABLE . ' where UF_NAME="' . $textureName
                . '" limit 1;')->fetch();
            if (!$texture) {
                $this->xmlId = md5($textureName);
                $connection->query('insert into ' . self::TEXTURE_TABLE . ' (UF_NAME, UF_XML_ID) values ("'
                    . $textureName . '", "' . $this->xmlId . '")');
            } else {
                $this->xmlId = $texture["UF_XML_ID"];
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