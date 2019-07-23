<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 16:06
 */

namespace Taber\BrandQuad\Utils;


use Bitrix\Main\Application;

class TaberBQDetail
{
    private $xmlId;
    private $name;

    const BQ_DETAIL_TABLE = 'hl_product_bq_detail';

    public function __construct(string $bqDetailName)
    {
        if ($bqDetailName) {
            $this->name = $bqDetailName;
            $connection = Application::getConnection();
            $bqDetail = $connection->query('SELECT * from ' . self::BQ_DETAIL_TABLE . ' where UF_NAME="' . $bqDetailName
                . '" limit 1;')->fetch();
            if (!$bqDetail) {
                $arParams = array("replace_space"=>"-","replace_other"=>"-");
                $this->xmlId = \CUtil::translit($bqDetailName,"ru",$arParams);
                $connection->query('insert into ' . self::BQ_DETAIL_TABLE . ' (UF_NAME, UF_XML_ID) values ("'
                    . $bqDetailName . '", "' . $this->xmlId . '")');
            } else {
                $this->xmlId = $bqDetail["UF_XML_ID"];
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