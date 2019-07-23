<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberHairEffect
{
    private $xmlId;
    private $name;

    const HAIR_EFFECT_TABLE = 'hl_product_hair_effect';

    public function __construct(string $hairEffectName)
    {
        if ($hairEffectName) {
            $this->name = $hairEffectName;
            $connection = Application::getConnection();
            $hairEffect = $connection->query('SELECT * from ' . self::HAIR_EFFECT_TABLE . ' where UF_NAME="' . $hairEffectName
                . '" limit 1;')->fetch();
            if (!$hairEffect) {
                $this->xmlId = md5($hairEffectName);
                $connection->query('insert into ' . self::HAIR_EFFECT_TABLE . ' (UF_NAME, UF_XML_ID) values ("'
                    . $hairEffectName . '", "' . $this->xmlId . '")');
            } else {
                $this->xmlId = $hairEffect["UF_XML_ID"];
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