<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 11.01.2019
 * Time: 14:20
 */

namespace Taber\BrandQuad\BrandQuadProcess;


use Bitrix\Main\Diag\Debug;
use Taber\BrandQuad\Utils\BrandQuadObject;
use Taber\BrandQuad\Utils\BrandQuadProduct;

/**
 * производит апдейт товара после CheckIfUpdated
 * или после CheckIfValidProduct - деактивирует или загружает новый товар если валидный и новый
 *
 * Далее отдает на обработку ProcessProductOffer
 * для работы с торговым предложением
 *
 * Class ProcessProduct
 * @package Taber\BrandQuad\BrandQuadProcess
 */

class ProcessProduct implements ProcessCatalog
{

    const PRODUCT_IBLOCK_ID = 1;

    /**
     * @param BrandQuadObject $brandQuadObject
     */
    public static function process(BrandQuadObject &$brandQuadObject)
    {
        if (!$brandQuadObject->isValid() && $brandQuadObject->isNew()) {
            /**
             *  НЕ новый и НЕ валидный, деактивируем товар
             * если он родитель, то родитель тоже деактивируется,
             * товары к нему привязанные на склейке переносятся на другой активный товар - родитель
             * до склейки на сайте не показываются товары из данной группы этого родителя
             */
            if ($brandQuadObject->isParent()){
                self::deactivateTaberProduct($brandQuadObject);
            }
        }
        if ($brandQuadObject->isNew() && $brandQuadObject->isValid() && $brandQuadObject->isUpdated()) {
            /**
             * не новый, валидный и требует апдейта, апдейтим
             */
            if ($brandQuadObject->isParent()) {
                self::updateTaberProduct($brandQuadObject);
            }
        }
        if(!$brandQuadObject->isNew() && !$brandQuadObject->isGrouping() && $brandQuadObject->isValid()){
            self::constructTaberProduct($brandQuadObject);
        }
        /**
         * отправляем процессу, обрабатывающему торговые предложения
         */
        ProcessProductOffer::process($brandQuadObject);
    }

    /**
     * деактивирует товар если он не валиден
     *
     * @param BrandQuadProduct $brandQuadProduct
     */
    private static function deactivateTaberProduct(BrandQuadProduct &$brandQuadProduct)
    {
        /**
         * если он является родителем, то тоже его деактивируем
         */
        if($brandQuadProduct->isParent()) {
            $taberProduct = new \CIBlockElement();
            $taberProductMainFields = [
                'IBLOCK_ID' => self::PRODUCT_IBLOCK_ID,
                'ACTIVE' => 'N',
            ];
            $taberProduct->Update($brandQuadProduct->isParent(), $taberProductMainFields);
            $errors["ошибка деактиваци товара"] = $taberProduct->LAST_ERROR;
            $brandQuadProduct->isDownloaded($errors);
        }
    }

    /**
     * апдейт товара
     *
     * @param BrandQuadProduct $brandQuadProduct
     */
    private static function updateTaberProduct(BrandQuadProduct &$brandQuadProduct)
    {
        $errors = [];
        $taberProduct = new \CIBlockElement();
        $translitParams = array("replace_space" => "-", "replace_other" => "-");
        /**
         * апдейт основных полей товара, апдейтим только измененные
         */
        $taberProductMainFields["IBLOCK_ID"] = self::PRODUCT_IBLOCK_ID;
        if ($brandQuadProduct->checkDiff('name')) {
            $taberProductMainFields["NAME"] = $brandQuadProduct->name();
            $taberProductMainFields["CODE"] = $brandQuadProduct->article() . '-' . \CUtil::translit($brandQuadProduct->name(), "ru", $translitParams);
        }
        if ($brandQuadProduct->checkDiff('category')) {
            $taberProductMainFields["IBLOCK_SECTION_ID"] = $brandQuadProduct->category();
        }
        if ($brandQuadProduct->checkDiff('productPhoto')) {
            /**
             * обновит картинку даже если совпадает, тк картинка сверяется
             * только с торговым предложением, если там не совпала
             * тут тоже обновится
             */
            foreach ($brandQuadProduct->photo() as $mainPhotoUrl => $mainPhoto) {
                $taberProductMainFields["PREVIEW_PICTURE"] = $taberProductMainFields["DETAIL_PICTURE"] = \CFile::MakeFileArray(
                    $mainPhotoUrl,
                    false,
                    false,
                    $mainPhoto
                );
                break;
            }
        }
        if ($brandQuadProduct->checkDiff('detailText')) {
            $taberProductMainFields["DETAIL_TEXT"] = nl2br($brandQuadProduct->detailText());
            $taberProductMainFields["DETAIL_TEXT_TYPE"] = "html";
        }
        /**
         * товары активируются при загрузке цен, а не тут
         */
//        if ($brandQuadProduct->checkDiff('active')) {
//            $taberProductMainFields["ACTIVE"] = 'Y';
//        }
        if (count($taberProductMainFields) > 1) {
            $taberProduct->Update($brandQuadProduct->isParent(), $taberProductMainFields);
            $errors["ошибки апдейт основных полей товара"] = $taberProduct->LAST_ERROR;
        }
        /**
         * апдейт свойств товара, апдейтим только измененные
         */
        $taberProductProperties = [];
        if ($brandQuadProduct->checkDiff('line')) {
            $taberProductProperties["PRODUCT_LINE"] = $brandQuadProduct->line();
        }
        if ($brandQuadProduct->checkDiff('video')) {
            $taberProductProperties["VIDEO"] = $brandQuadProduct->video();
        }
        if ($brandQuadProduct->checkDiff('weight')) {
            $taberProductProperties["WEIGHT"] = $brandQuadProduct->weight();
        }
        if ($brandQuadProduct->checkDiff('brand')) {
            $taberProductProperties["BRAND"] = $brandQuadProduct->brand();
        }
        if ($brandQuadProduct->checkDiff('applyingType')) {
            $taberProductProperties["USAGE"] = ['TEXT' => trim($brandQuadProduct->applyingType()), 'TYPE' => 'HTML'];
        }
        if ($brandQuadProduct->checkDiff('ingredients')) {
            $taberProductProperties["INGREDIENTS"] = ['TEXT' => trim($brandQuadProduct->ingredients()), 'TYPE' => 'HTML'];
        }
        if ($brandQuadProduct->checkDiff('gender')) {
            $taberProductProperties["GENDER"] = $brandQuadProduct->gender();
        }
        if ($brandQuadProduct->checkDiff('age')) {
            $taberProductProperties["AGE"] = $brandQuadProduct->age();
        }
        if ($brandQuadProduct->checkDiff('eko')) {
            $taberProductProperties["EKO"] = $brandQuadProduct->eko();
        }
        if ($brandQuadProduct->checkDiff('bodyEffect')) {
            $taberProductProperties["SKIN_FACE_EFFECT"] = $brandQuadProduct->bodyEffect();
        }
        if ($brandQuadProduct->checkDiff('hairEffect')) {
            $taberProductProperties["HAIR_TYPE"] = $brandQuadProduct->hairEffect();
        }
        if ($brandQuadProduct->checkDiff('spf')) {
            $taberProductProperties["SPF"] = $brandQuadProduct->spf();
        }
        if ($brandQuadProduct->checkDiff('BQDetail')) {
            $taberProductProperties["BQ_DETAIL"] = $brandQuadProduct->BQDetail();
        }
        if ($brandQuadProduct->checkDiff('hairEffect')) {
            $taberProductProperties["HAIR_EFFECT"] = $brandQuadProduct->hairEffect();
        }
        if ($brandQuadProduct->checkDiff('faceUse')) {
            $taberProductProperties["FACE_USE"] = $brandQuadProduct->faceUse();
        }
        if ($brandQuadProduct->checkDiff('texture')) {
            $taberProductProperties["TEXTURE"] = $brandQuadProduct->texture();
        }
        if ($brandQuadProduct->checkDiff('skinType')) {
            $taberProductProperties["SKIN_TYPE"] = $brandQuadProduct->skinType();
        }
        if ($brandQuadProduct->checkDiff('areaOfUse')) {
            $taberProductProperties["AREA_OF_USE"] = $brandQuadProduct->areaOfUse();
        }
        if ($brandQuadProduct->checkDiff('country')) {
            $taberProductProperties["COUNTRY"] = $brandQuadProduct->country();
        }
        if (count($taberProductProperties) > 0) {
            $taberProduct->SetPropertyValuesEx($brandQuadProduct->isParent(), self::PRODUCT_IBLOCK_ID, $taberProductProperties);
            $errors["ошибки апдейт свойств товара"] = $taberProduct->LAST_ERROR;
        }
        if ($errors) {
            $brandQuadProduct->isDownloaded($errors);
        }
    }

    /**
     * создаем товар для торгового предложения, которое не в разделе с группировкой по линейке
     *
     * @param BrandQuadProduct $brandQuadProduct
     * @return bool|int
     */
    public static function constructTaberProduct(BrandQuadProduct $brandQuadProduct)
    {
        $taberProduct = new \CIBlockElement();
        $translitParams = array("replace_space" => "-", "replace_other" => "-");
        /**
         * основные поля товара
         * новый товар загружается неактивным, активируется при загрузке цен, не тут
         */
        $taberProductMainFields = [
            'IBLOCK_ID' => self::PRODUCT_IBLOCK_ID,
            'NAME' => $brandQuadProduct->name(),
            'CODE' => $brandQuadProduct->article() . '-' . \CUtil::translit($brandQuadProduct->name(), "ru", $translitParams),
            'XML_ID' => $brandQuadProduct->article(),
            'IBLOCK_SECTION_ID' => $brandQuadProduct->category(),
            'ACTIVE' => 'N',
            'DETAIL_TEXT' => nl2br($brandQuadProduct->detailText()),
            'DETAIL_TEXT_TYPE' => 'html'
        ];
        foreach ($brandQuadProduct->photo() as $mainPhotoUrl => $mainPhoto) {
            $taberProductMainFields["PREVIEW_PICTURE"] = $taberProductMainFields["DETAIL_PICTURE"] = \CFile::MakeFileArray(
                $mainPhotoUrl,
                false,
                false,
                $mainPhoto
            );
            break;
        }
        $newProductId = $taberProduct->add($taberProductMainFields);
        $brandQuadProduct->isParent($newProductId);
        $errors["ошибки основные поля товара"] = $taberProduct->LAST_ERROR;
        /**
         * свойства товара
         */
        $taberProductProperties = [
            'ARTICLE' => $brandQuadProduct->article(),
            'PRODUCT_LINE' => $brandQuadProduct->line(),
            'VIDEO' => $brandQuadProduct->video(),
            'WEIGHT' => $brandQuadProduct->weight(),
            'BRAND' => $brandQuadProduct->brand(),
            'USAGE' => ['TEXT' => trim($brandQuadProduct->applyingType()), 'TYPE' => 'HTML'],
            'INGREDIENTS' => ['TEXT' => trim($brandQuadProduct->ingredients()), 'TYPE' => 'HTML'],
            'GENDER' => $brandQuadProduct->gender(),
            'AGE' => $brandQuadProduct->age(),
            'EKO' => $brandQuadProduct->eko(),
            'SKIN_FACE_EFFECT' => $brandQuadProduct->bodyEffect(),
            'HAIR_TYPE' => $brandQuadProduct->hairEffect(),
            'SPF' => $brandQuadProduct->spf(),
            'BQ_DETAIL' => $brandQuadProduct->BQDetail(),
            'HAIR_EFFECT' => $brandQuadProduct->hairEffect(),
            'FACE_USE' => $brandQuadProduct->faceUse(),
            'TEXTURE' => $brandQuadProduct->texture(),
            'SKIN_TYPE' => $brandQuadProduct->skinType(),
            'AREA_OF_USE' => $brandQuadProduct->areaOfUse(),
            'COUNTRY' => $brandQuadProduct->country()
        ];
        foreach ($brandQuadProduct->galleryPhoto() as $galleryPhotoUrl => $galleryPhoto) {
            $taberProductProperties["GALLERY"] = \CFile::MakeFileArray(
                $galleryPhotoUrl,
                false,
                false,
                $galleryPhoto
            );
        }
        $taberProduct->SetPropertyValuesEx($brandQuadProduct->isParent(), self::PRODUCT_IBLOCK_ID, $taberProductProperties);
        $errors["ошибки свойства товара"] = $taberProduct->LAST_ERROR;
        $brandQuadProduct->isDownloaded($errors);
        return $newProductId;
    }
}

