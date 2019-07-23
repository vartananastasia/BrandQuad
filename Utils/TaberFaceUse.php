<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberFaceUse
{
    private $xmlId;
    private $name;

    const FACE_USE_TABLE = 'hl_product_age';

    public function __construct(string $faceUseName)
    {
        if ($faceUseName) {
            $this->name = $faceUseName;
            $connection = Application::getConnection();
            $faceUse = $connection->query('SELECT * from ' . self::FACE_USE_TABLE . ' where UF_NAME="' . $faceUseName
                . '" limit 1;')->fetch();
            if (!$faceUse) {
                $this->xmlId = md5($faceUseName);
                $connection->query('insert into ' . self::FACE_USE_TABLE . ' (UF_NAME, UF_XML_ID) values ("'
                    . $faceUseName . '", "' . $this->xmlId . '")');
            } else {
                $this->xmlId = $faceUse["UF_XML_ID"];
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