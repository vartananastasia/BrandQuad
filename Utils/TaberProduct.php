<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 18.01.2019
 * Time: 10:58
 */

namespace Taber\BrandQuad\Utils;


class TaberProduct
{
    private $product;

    public function __construct($productId)
    {
        /**
         * в товаре массив из основных полей и свойств из торгового предложения
         */
        $dbEl = \CIBlockElement::GetByID($productId);
        if ($obEl = $dbEl->GetNextElement()) {
            $this->product = $obEl->getFields();
            $arProps = $obEl->GetProperties();
            foreach ($arProps as $arProp) {
                $this->product[$arProp["CODE"]] = $arProp["VALUE"];
            }
        }
        /**
         * и добавляем к массиву свойства его родителя
         */
        if ($this->product["PRODUCT"]) {
            $dbEl = \CIBlockElement::GetByID($this->product["PRODUCT"]);
            if ($obEl = $dbEl->GetNextElement()) {
                $this->product["SECTION"] = $obEl->getFields()["IBLOCK_SECTION_ID"];
                $this->product["PRODUCT_PHOTO"] = $obEl->getFields()["DETAIL_PICTURE"];
                $arProps = $obEl->GetProperties();
                foreach ($arProps as $arProp) {
                    if ($arProp["CODE"] != "GALLERY") {  // берем картинки галереи только из торгового предложения
                        $this->product[$arProp["CODE"]] = $arProp["VALUE"];
                    }
                }
            }
        }
        /**
         * эти поля пишем всегда при апдейте:
         * $taberProduct->product()["BQ_UPDATE"]
         */
    }

    public function category()
    {
        return $this->product["SECTION"];
    }

    public function active()
    {
        return $this->product["ACTIVE"];
    }

    public function activeFrom()
    {
        return $this->product["ACTIVE_FROM"];
    }

    public function name()
    {
        return $this->product["NAME"];
    }

    public function BQDetail()
    {
        return $this->product["BQ_DETAIL"][0] ? $this->product["BQ_DETAIL"][0] : '';
    }

    public function ingredients()
    {
        return $this->product["INGREDIENTS"]["TEXT"];
    }

    public function applyingType()
    {
        return $this->product["USAGE"]["TEXT"];
    }

    public function texture()
    {
        return $this->product["TEXTURE"] ? $this->product["TEXTURE"] : '';
    }

    public function faceUse()
    {
        return $this->product["FACE_USE"][0] ? $this->product["FACE_USE"][0] : '';
    }

    public function weight()
    {
        return $this->product["WEIGHT"];
    }

    public function hairEffect()
    {
        return $this->product["HAIR_EFFECT"] ? $this->product["HAIR_EFFECT"] : '';
    }

    public function hairType()
    {
        return $this->product["HAIR_TYPE"] ? $this->product["HAIR_TYPE"] : '';
    }

    public function spf()
    {
        return $this->product["SPF"][0] ? $this->product["SPF"][0] : '';
    }

    public function skinEffect()
    {
        return $this->product["SKIN_FACE_EFFECT"];
    }

    public function bodyEffect()
    {
        return $this->product["SKIN_FACE_EFFECT"][0] ? $this->product["SKIN_FACE_EFFECT"][0] : '';
    }

    public function skinType()
    {
        return $this->product["SKIN_TYPE"] ? $this->product["SKIN_TYPE"] : '';
    }

    public function eko()
    {
        return $this->product["EKO"] ? $this->product["EKO"] : '';
    }

    public function country()
    {
        return $this->product["COUNTRY"];
    }

    public function gender()
    {
        return $this->product["GENDER"][0] ? $this->product["GENDER"][0] : '';
    }

    public function areaOfUse()
    {
        return $this->product["AREA_OF_USE"] ? $this->product["AREA_OF_USE"] : '';
    }

    public function age()
    {
        return $this->product["AGE"][0] ? $this->product["AGE"][0] : '';
    }

    public function detailText()
    {
        return $this->product["DETAIL_TEXT"];
    }

    public function article()
    {
        return $this->product["ARTICLE"];
    }

    public function photo()
    {
        $photo = \CFile::GetFileArray($this->product["DETAIL_PICTURE"]);
        return $photo["EXTERNAL_ID"] ? $photo["EXTERNAL_ID"] : '';
    }

    public function productPhoto()
    {
        $photo = \CFile::GetFileArray($this->product["PRODUCT_PHOTO"]);
        return $photo["EXTERNAL_ID"] ? $photo["EXTERNAL_ID"] : '';
    }

    public function galleryPhoto()
    {
        $photos = [];
        if ($this->product["GALLERY"]) {
            foreach ($this->product["GALLERY"] as $photo) {
                $photos[$photo] = \CFile::GetFileArray($photo)["EXTERNAL_ID"];
            }
        }
        return $photos;
    }

    public function barcode()
    {
        return $this->product["EAN"];
    }

    public function tonePhoto()
    {
        return $this->product["TONE"];
    }

    public function line()
    {
        return $this->product["PRODUCT_LINE"];
    }

    public function video()
    {
        return $this->product["VIDEO"][0];
    }

    public function brand()
    {
        return $this->product["BRAND"];
    }

    public function product()
    {
        return $this->product;
    }
}