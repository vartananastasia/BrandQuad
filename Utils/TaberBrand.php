<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 22.01.2019
 * Time: 14:35
 */

namespace Taber\BrandQuad\Utils;


class TaberBrand
{
    private $id;
    const BRAND_IBLOCK_CODE = 'brand';
    const BRAND_IBLOCK_ID = 7;  // TODO: заменить

    public function __construct(string $brandName)
    {
        $brand = \CIBlockElement::GetList([], ["IBLOCK_ID" => self::BRAND_IBLOCK_ID, "NAME" => $brandName], false, false, ["*"])->getNext();
        if (!$brand) {
            $brand = \CIBlockElement::GetList([], ["IBLOCK_ID" => self::BRAND_IBLOCK_ID, "CODE" => \create_code($brandName)], false, false, ["*"])->getNext();
        }
        if (!$brand) {
            $newBrand = new \CIBlockElement();
            $newBrandId = $newBrand->Add([
                "IBLOCK_ID" => self::BRAND_IBLOCK_ID,
                "NAME" => $brandName,
                "ACTIVE" => "Y",
                'CODE' => \create_code($brandName),
                'XML_ID' => \create_code($brandName)
            ]);
            $this->id = $newBrandId;
        } else {
            $this->id = $brand["ID"];
        }
    }

    public function id()
    {
        return $this->id;
    }
}