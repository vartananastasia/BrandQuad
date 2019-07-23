<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberBodyEffect
{
    private $xmlId;
    private $name;

    const BODY_EFFECT_TABLE = 'hl_product_skin_face_effect';

    public function __construct(string $bodyEffectName)
    {
        if ($bodyEffectName) {
            $this->name = $bodyEffectName;
            $connection = Application::getConnection();
            $bodyEffect = $connection->query('SELECT * from ' . self::BODY_EFFECT_TABLE . ' where UF_NAME="' . $bodyEffectName
                . '" limit 1;')->fetch();
            if (!$bodyEffect) {
                $this->xmlId = md5($bodyEffectName);
                $connection->query('insert into ' . self::BODY_EFFECT_TABLE . ' (UF_NAME, UF_XML_ID) values ("'
                    . $bodyEffectName . '", "' . $this->xmlId . '")');
            } else {
                $this->xmlId = $bodyEffect["UF_XML_ID"];
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