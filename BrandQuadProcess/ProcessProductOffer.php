<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 11.01.2019
 * Time: 14:20
 */

namespace Taber\BrandQuad\BrandQuadProcess;


use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Type\DateTime;
use Taber\BrandQuad\Utils\BrandQuadObject;
use Taber\BrandQuad\Utils\BrandQuadProduct;

/**
 * Завершает обработку
 *
 * Последний в цепочке обработки, апдейтит/деактивирует/добавляет торговое предложение
 *
 * и ПИШЕТ РЕЗУЛЬТАТ ОБРАБОТКИ ТОВАРА В КЕШ НА 20 ЧАСОВ
 * BrandQuadProduct::cacheProductResult($brandQuadObject->article()) - тут можно посмотреть
 * в течение 20 часов все ошибки загрузки и поля апдейта.
 *
 * Class ProcessProductOffer
 * @package Taber\BrandQuad\BrandQuadProcess
 */
class ProcessProductOffer implements ProcessCatalog
{

    const OFFER_IBLOCK_ID = 12;
    const OFFER_TYPE = 4;

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
            self::deactivateTaberOffer($brandQuadObject);
        }
        if (!$brandQuadObject->isNew() && $brandQuadObject->isValid()) {
            /**
             * новый и валидный, создаем новое торговое предложение
             * при втором проходе создается товар,
             * если не с кем склеить торговое предложение которое было создано
             */
            self::constructTaberOffer($brandQuadObject);
        }
        if ($brandQuadObject->isNew() && $brandQuadObject->isValid()) {
            /**
             * не новый, валидный и требует апдейта(или не требует, тогда просто запишем ему дату синхронизации и
             * хеш склейки), апдейтим
             */
            self::updateTaberOffer($brandQuadObject);
        }
        if ($brandQuadObject->isGrouping() && $brandQuadObject->isValid()) {
            $brandQuadObject->cacheProduct();
            self::groupCacheArticle($brandQuadObject->article());
        }
    }

    /**
     * пишем артикулы в мемкеш для склейки после загрузки
     *
     * @param $article
     */
    private function groupCacheArticle($article)
    {
        $phpMemcached = new \Memcached;
        $phpMemcached->addServer('memcached.internal', 11211);
        $groupingArticles = json_decode($phpMemcached->get("BQ_grouping"), true);
        if (!in_array($article, $groupingArticles)) {
            $groupingArticles[] = $article;
        }
        $phpMemcached->set('BQ_grouping', json_encode($groupingArticles), time() + 60 * 60 * 20);
    }

    /**
     * деактивируем торговое предложение если оно не валидно
     *
     * @param BrandQuadProduct $brandQuadProduct
     */
    private static function deactivateTaberOffer(BrandQuadProduct &$brandQuadProduct)
    {
        $taberProduct = new \CIBlockElement();
        $taberOfferMainFields = [
            'IBLOCK_ID' => self::OFFER_IBLOCK_ID,
            'ACTIVE' => 'N',
        ];
        $taberProduct->Update($brandQuadProduct->isNew(), $taberOfferMainFields);
        $errors["ошибка деактиваци торгового предложения"] = $taberProduct->LAST_ERROR;
        $taberOfferProperties = [
            "BQ_UPDATE" => DateTime::createFromTimestamp(time()),
        ];
        $taberProduct->SetPropertyValuesEx($brandQuadProduct->isNew(), self::OFFER_IBLOCK_ID, $taberOfferProperties);
        $errors["ошибка записи даты апдейта торгового предложения"] = $taberProduct->LAST_ERROR;
        $brandQuadProduct->isDownloaded($errors);
    }

    /**
     * создаем торговое предлоежние, для разделов с группировкой торговое предложение создается без товара,
     * при склейке ему записывается товар, либо создается товар, если склеить не с кем
     *
     * @param BrandQuadProduct $brandQuadProduct
     */
    private static function constructTaberOffer(BrandQuadProduct &$brandQuadProduct)
    {
        $taberProduct = new \CIBlockElement();
        /**
         * основные поля торгового предложения
         * новое торговое предложение загружается как неактивное, активируется при выгрузке цен
         */
        $translationParams = array("replace_space" => "-", "replace_other" => "-");
        $taberOfferMainFields = [
            'IBLOCK_ID' => self::OFFER_IBLOCK_ID,
            'NAME' => $brandQuadProduct->name(),
            'CODE' => $brandQuadProduct->article() . '-' . \CUtil::translit($brandQuadProduct->name(), "ru", $translationParams),
            'XML_ID' => $brandQuadProduct->article(),
            'ACTIVE' => 'N',
            'ACTIVE_FROM' => DateTime::createFromTimestamp(time()),
            'DETAIL_TEXT' => $brandQuadProduct->detailText(),
            'DETAIL_TEXT_TYPE' => 'html'
        ];
        foreach ($brandQuadProduct->photo() as $mainPhotoUrl => $mainPhoto) {
            $taberOfferMainFields["PREVIEW_PICTURE"] = $taberOfferMainFields["DETAIL_PICTURE"] = \CFile::MakeFileArray(
                $mainPhotoUrl,
                false,
                false,
                $mainPhoto
            );
            break;
        }
        $newProductId = $taberProduct->Add($taberOfferMainFields);
        \CPrice::Add([
            'PRODUCT_ID' => $newProductId,
            'CATALOG_GROUP_ID' => 1,
            'PRICE' => 0,
            'CURRENCY' => 'RUB'
        ]);
        $catalogProduct = new \CCatalogProduct();
        $catalogProductFields = [
            'ID' => $newProductId,
            'VAT_INCLUDED' => 'Y',
            'WEIGHT' => (float)str_replace([',', ' '], ['.', ''], $brandQuadProduct->weight()),
            'HEIGHT' => (float)str_replace([',', ' '], ['.', ''], $brandQuadProduct->height()),
            'WIDTH' => (float)str_replace([',', ' '], ['.', ''], $brandQuadProduct->width()),
            'LENGTH' => (float)str_replace([',', ' '], ['.', ''], $brandQuadProduct->length()),
            'TYPE' => self::OFFER_TYPE,
        ];
        $catalogProduct->add($catalogProductFields);
        $errors["ошибки основные поля торгового предложения"] = $taberProduct->LAST_ERROR;
        $errors["ошибки габариты торгового предложения"] = $catalogProduct->LAST_ERROR;
        /**
         * свойства торгового предложения
         */
        if ($newProductId) {
            $taberOfferProperties = [
                "GROUP_HASH" => $brandQuadProduct->groupHash(),
                "BQ_UPDATE" => DateTime::createFromTimestamp(time()),
                'ARTICLE' => $brandQuadProduct->article(),
                'TONE' => $brandQuadProduct->tonePhoto(),
                'EAN' => $brandQuadProduct->barcode(),
                'PRODUCT' => $brandQuadProduct->isParent()
            ];
            foreach ($brandQuadProduct->galleryPhoto() as $galleryPhotoUrl => $galleryPhoto) {
                $taberOfferProperties["GALLERY"] = \CFile::MakeFileArray(
                    $galleryPhotoUrl,
                    false,
                    false,
                    $galleryPhoto
                );
            }
            $taberProduct->SetPropertyValuesEx($newProductId, self::OFFER_IBLOCK_ID, $taberOfferProperties);
            $errors["ошибки свойства торгового предложения"] = $taberProduct->LAST_ERROR;
        }
        $brandQuadProduct->isDownloaded($errors);
    }

    /**
     * апдейтим торговое предложение
     *
     * @param BrandQuadProduct $brandQuadProduct
     */
    private static function updateTaberOffer(BrandQuadProduct &$brandQuadProduct)
    {
        $taberProduct = new \CIBlockElement();
        /**
         * апдейт основных полей торгового предложения, апдейтим только измененные
         */
        $taberOfferMainFields["IBLOCK_ID"] = self::OFFER_IBLOCK_ID;
        /**
         * надо активировать товар, если он был деактивирован
         */
        if ($brandQuadProduct->checkDiff('name')) {
            $taberOfferMainFields["NAME"] = $brandQuadProduct->name();
            $translationParams = array("replace_space" => "-", "replace_other" => "-");
            $taberOfferMainFields["CODE"] = $brandQuadProduct->article() . '-' . \CUtil::translit($brandQuadProduct->name(), "ru", $translationParams);
        }
        /**
         * товары активируются при загрузке цен, но если не проставить дату начала активации то торговое предложение не
         * привязывается к битрикс товару
         */
        if ($brandQuadProduct->checkDiff('active_from')) {
            $taberOfferMainFields["ACTIVE_FROM"] = DateTime::createFromTimestamp(time());
        }
        if ($brandQuadProduct->checkDiff('photo')) {
            foreach ($brandQuadProduct->photo() as $mainPhotoUrl => $mainPhoto) {
                $taberOfferMainFields["PREVIEW_PICTURE"] = $taberOfferMainFields["DETAIL_PICTURE"] = \CFile::MakeFileArray(
                    $mainPhotoUrl,
                    false,
                    false,
                    $mainPhoto
                );
                break;
            }
        }
        if ($brandQuadProduct->checkDiff('detailText')) {
            $taberOfferMainFields["DETAIL_TEXT"] = $brandQuadProduct->detailText();
            $taberOfferMainFields["DETAIL_TEXT_TYPE"] = "html";
        }
        if (count($taberOfferMainFields) > 1) {
            $taberProduct->Update($brandQuadProduct->isNew(), $taberOfferMainFields);
            $errors["ошибки апдейт основных полей торгового предложения"] = $taberProduct->LAST_ERROR;
        }

        /**
         * апдейт свойств предложения, апдейтим только измененные
         * и обязательно апдейтим поле BQ_UPDATE чтобы знать что сегодня товар обновился.
         * Если BQ_UPDATE при втором проходе не свежий, значит товар удален из BQ и мы его у себя
         * деактвируем
         */
        $taberOfferProperties["BQ_UPDATE"] = DateTime::createFromTimestamp(time());
        $taberOfferProperties["GROUP_HASH"] = $brandQuadProduct->groupHash();
        if ($brandQuadProduct->checkDiff('tonePhoto')) {
            $taberOfferProperties["TONE"] = $brandQuadProduct->tonePhoto();
        }
        if ($brandQuadProduct->checkDiff('barcode')) {
            $taberOfferProperties["EAN"] = $brandQuadProduct->barcode();
        }
        if ($brandQuadProduct->checkDiff('galleryAdd')) {
            foreach ($brandQuadProduct->galleryPhoto() as $photoUrl => $galleryPhoto) {
                $taberOfferProperties["GALLERY"][] = \CFile::MakeFileArray(
                    $photoUrl,
                    false,
                    false,
                    $galleryPhoto
                );
            }
        }
        /**
         * удаляем лишние фото из свойства GALLERY
         */
        elseif ($brandQuadProduct->checkDiff('galleryDel')) {
            $picturesIds = array_keys($brandQuadProduct->diff()["galleryDel"]);
            $elementPictures = \CIBlockElement::GetProperty(self::OFFER_IBLOCK_ID, $brandQuadProduct->isNew(), false, false, array('CODE' => 'GALLERY'));
            while ($elementPicture = $elementPictures->Fetch()) {
                if (in_array($elementPicture["VALUE"], $picturesIds)) {
                    \CFile::Delete($elementPicture['VALUE']);
                    \CIBlockElement::SetPropertyValueCode(
                        $brandQuadProduct->isNew(),
                        "GALLERY",
                        [$elementPicture['PROPERTY_VALUE_ID'] => ["VALUE" => ["del" => "Y"]]]);
                }
            }
        }
        $taberProduct->SetPropertyValuesEx($brandQuadProduct->isNew(), self::OFFER_IBLOCK_ID, $taberOfferProperties);
        $errors["ошибки апдейт свойств предложения"] = $taberProduct->LAST_ERROR;
        $errors = self::updateOfferDimensions($brandQuadProduct, $errors);
        $brandQuadProduct->isDownloaded($errors);
    }

    /**
     * @param BrandQuadProduct $brandQuadProduct
     * @param $errors
     * @return mixed
     */
    private static function updateOfferDimensions(BrandQuadProduct &$brandQuadProduct, $errors)
    {
        /**
         * тут отдельно проверяем указаны ли габариты торгового предложения и
         * является ли торговое предложение типом товара с TYPE=4 "предложение"
         */
        $product = \CCatalogProduct::GetByID($brandQuadProduct->isNew());
        $catalogProductFields["ID"] = $brandQuadProduct->isNew();
        ($product["TYPE"] != self::OFFER_TYPE) ?
            $catalogProductFields["TYPE"] = self::OFFER_TYPE : false;
        (!$product["WEIGHT"]) ?
            $catalogProductFields["WEIGHT"] = (float)str_replace([',', ' '], ['.', ''], $brandQuadProduct->weight()) : false;
        (!$product["HEIGHT"]) ?
            $catalogProductFields["HEIGHT"] = (float)str_replace([',', ' '], ['.', ''], $brandQuadProduct->height()) : false;
        (!$product["WIDTH"]) ?
            $catalogProductFields["WIDTH"] = (float)str_replace([',', ' '], ['.', ''], $brandQuadProduct->width()) : false;
        (!$product["LENGTH"]) ?
            $catalogProductFields["LENGTH"] = (float)str_replace([',', ' '], ['.', ''], $brandQuadProduct->length()) : false;
        if (count($catalogProductFields) > 1) {
            $catalogProduct = new \CCatalogProduct();
            $catalogProduct->add($catalogProductFields);
            $errors["ошибки апдейт габаритов предложения"] = $catalogProduct->LAST_ERROR;
        }
        return $errors;
    }
}