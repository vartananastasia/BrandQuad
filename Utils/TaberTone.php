<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberTone
{
    private $xmlId;

    const TONE_TABLE = 'hl_tone';

    public function __construct(array $tonePhoto)  // тон может записать только юзер webadmin
    {
        if ($tonePhoto["hash"]) {
            $this->xmlId = $tonePhoto["hash"];
            $connection = Application::getConnection();
            $tone = $connection->query('SELECT * from ' . self::TONE_TABLE . ' where UF_XML_ID="' . $this->xmlId
                . '" limit 1;')->fetch();
            if (!$tone) {
                $file = \CFile::MakeFileArray($tonePhoto["url"], false, false, $tonePhoto["hash"]);
                $filePhotoId = \CFile::SaveFile($file, '/tone/');
                if ($filePhotoId) {
                    $connection->query('insert into ' . self::TONE_TABLE . ' (UF_NAME, UF_ACTIVE, UF_DEF, UF_XML_ID, UF_FILE) values ("'
                        . $tonePhoto["article"] . '", "Y", "N", "' . $this->xmlId . '", ' . $filePhotoId . ')');
                } else {
                    $this->xmlId = $tone["UF_XML_ID"];
                }
            } else {
                $this->xmlId = $tone["UF_XML_ID"];
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