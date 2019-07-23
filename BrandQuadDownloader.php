<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 11.01.2019
 * Time: 15:08
 */

namespace Taber\BrandQuad;

use Bitrix\Main\Diag\Debug;
use Taber\BrandQuad\BrandQuadProcess\ProcessProduct;
use Taber\BrandQuad\BrandQuadProcess\ProcessProductOffer;
use Taber\BrandQuad\BrandQuadProcess\StartProcessing;
use Taber\BrandQuad\Methods\BrandQuadMethod;


class BrandQuadDownloader
{
    /**
     * Сделает одноразовый запрос только к первой странице API BQ
     *
     * @param BrandQuadClient $brandQuadClient
     * @param BrandQuadMethod $brandQuadMethod
     */
    public static function start(BrandQuadClient &$brandQuadClient, BrandQuadMethod &$brandQuadMethod)
    {
        /**
         * для перезапуска загрузки с нуля, или какого то определнного шага
         * нужно стереть весь лог из сегодняшнего файла либо стереть этот шаг из лога
         *
         * если шаг присутствует в логе, то он не выполняется повторно
         */
        if (!self::checkDownloadUrl('--end--')) {
            if (!self::checkDownloadUrl($brandQuadMethod->url())) {
                self::process($brandQuadClient, $brandQuadMethod);
            } else {
                $brandQuadMethod->resetProductsPage($brandQuadMethod->page() + 1);
                self::start($brandQuadClient, $brandQuadMethod);
            }
        }
    }

    /**
     * Обрабатывает то, что пришло из BQ,
     * передавая полученный объект по цепочке ответственности (BrandQuadProcess)
     *
     * @param BrandQuadClient $brandQuadClient
     * @param BrandQuadMethod $brandQuadMethod
     * @param bool $repeat
     */
    public static function process(BrandQuadClient $brandQuadClient, BrandQuadMethod $brandQuadMethod, $repeat = false)
    {
        self::writeDebugToFile($brandQuadMethod->url());
        $brandQuadClient->executeMethod($brandQuadMethod);
        if (count($brandQuadClient->result()) > 0) {
            foreach ($brandQuadClient->result() as $brandQuadObject) {
                /**
                 * чтобы загрузка не отваливалась при неожиданном эксепшене, а проходила всегда до конца
                 */
                try {
                    StartProcessing::process($brandQuadObject);
                } catch (\Exception $e) {
                    Debug::dumpToFile([$brandQuadObject->article(), $brandQuadClient->nextUrl(), $e], 'error_' . date("d.m.Y H:i:s"), '/_log/BQ/BQErrorLog.txt');
                }
            }
            if ($repeat && $brandQuadClient->nextUrl()) {
                $brandQuadMethod->resetProductsPage($brandQuadMethod->page() + 1);
                self::repeat($brandQuadClient, $brandQuadMethod);
            }
        } else {
            self::writeDebugToFile('--end--');
        }
    }

    /**
     * Будет делать запросы пока не дойдет до последней страницы
     * и обработает все полученные резальтаты
     *
     * @param BrandQuadClient $brandQuadClient
     * @param BrandQuadMethod $brandQuadMethod
     */
    public static function repeat(BrandQuadClient &$brandQuadClient, BrandQuadMethod $brandQuadMethod)
    {
        /**
         * можно запускать несколько процессов одновременно
         */
        if (!self::checkDownloadUrl('--end--')) {
            if (!self::checkDownloadUrl($brandQuadMethod->url())) {
                self::process($brandQuadClient, $brandQuadMethod, true);
            } else {
                $brandQuadMethod->resetProductsPage($brandQuadMethod->page() + 1);
                self::repeat($brandQuadClient, $brandQuadMethod);
            }
        }
    }

    /**
     * проверить отработала ли ссылка в эту загрузку
     *
     * @param $url
     * @return bool
     */
    public static function checkDownloadUrl($url)
    {
        $downloaded = false;
        $downloads = file($_SERVER['DOCUMENT_ROOT'] . '/_log/BQ/bq_v2_' . date("d.m.Y") . '.txt');
        if (!$downloads) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . '/_log/BQ/');
        }
        foreach ($downloads as $download) {
            if (trim($download) == trim($url)) {
                $downloaded = true;
                break;
            }
        }
        return $downloaded;
    }

    /**
     * тут идет проход по всем товарам и деактивация удаленных из BQ
     */
    public function checkResult()
    {
        if (self::checkDownloadUrl('--end--') && !self::checkDownloadUrl('--updating--')) {
            self::writeDebugToFile('--updating--');
            /**
             * выбираем все товары со старой датой апдейта либо с отсутствующей датой апдейта и деактивируем их
             */
            $products = \CIBlockElement::GetList([], ["IBLOCK_ID" => ProcessProductOffer::OFFER_IBLOCK_ID, "ACTIVE" => "Y", ["LOGIC" => "OR",
                "<PROPERTY_BQ_UPDATE" => date("Y-m-d 00:00:00"), "PROPERTY_BQ_UPDATE" => false]],
                false, false, ["ID", "PROPERTY_ARTICLE"]);
            while ($product = $products->GetNext()) {
                $taberProduct = new \CIBlockElement();
                $taberProductMainFields = [
                    'IBLOCK_ID' => ProcessProductOffer::OFFER_IBLOCK_ID,
                    'ACTIVE' => 'N',
                ];
                $taberProduct->Update($product["ID"], $taberProductMainFields);
                $productParent = \CIBlockElement::GetList([], ["IBLOCK_ID" => ProcessProduct::PRODUCT_IBLOCK_ID, "ACTIVE" => "Y", "PROPERTY_ARTICLE" => $product["PROPERTY_ARTICLE_VALUE"]],
                    false, false, ["ID", "PROPERTY_ARTICLE"])->GetNext();
                if ($productParent["ID"]) {
                    $taberProductParentMainFields = [
                        'IBLOCK_ID' => ProcessProduct::PRODUCT_IBLOCK_ID,
                        'ACTIVE' => 'N',
                    ];
                    $taberProduct->Update($productParent["ID"], $taberProductParentMainFields);
                }
            }
            self::writeDebugToFile('--updating-complete--');
        }
    }

    /**
     * склейка
     */
    public function groupProducts()
    {
        $groupingTimeHour = 6;
        $groupingTimeMinute = 10;
        $currentHour = date('H');
        $currentMinute = date('i');
        $doGrouping = ($currentHour == $groupingTimeHour && $groupingTimeMinute == $currentMinute);
        if ((self::checkDownloadUrl('--end--') && self::checkDownloadUrl('--updating-complete--') && !self::checkDownloadUrl('--grouping--')) ||
            (self::checkDownloadUrl('--grouping-complete--') && $doGrouping)) {
            self::writeDebugToFile('--grouping--');
            $phpMemcached = new \Memcached;
            $phpMemcached->addServer('memcached.internal', 11211);
            $groupingArticles = json_decode($phpMemcached->get("BQ_grouping"), true);
            if (count($groupingArticles) > 0) {
                $groupingProducts = [];
                $products = \CIBlockElement::GetList([], ["IBLOCK_ID" => ProcessProductOffer::OFFER_IBLOCK_ID, "XML_ID" => $groupingArticles],
                    false, false, ["ID", "PROPERTY_ARTICLE", "ACTIVE", "PROPERTY_PRODUCT", "PROPERTY_PRODUCT.ACTIVE", "PROPERTY_PRODUCT.XML_ID", "PROPERTY_ARTICLE", "PROPERTY_GROUP_HASH"]);
                while ($product = $products->GetNextElement()) {
                    $productFields = $product->GetFields();
                    if ($productFields["PROPERTY_GROUP_HASH_VALUE"]) {
                        if ($productFields["PROPERTY_PRODUCT_ACTIVE"] == "Y" && $productFields["PROPERTY_ARTICLE_VALUE"] == $productFields["PROPERTY_PRODUCT_XML_ID"]) {
                            $groupingProducts[$productFields["PROPERTY_GROUP_HASH_VALUE"]]["parent"][] = $productFields;
                        } else {
                            $groupingProducts[$productFields["PROPERTY_GROUP_HASH_VALUE"]][$productFields["PROPERTY_ARTICLE_VALUE"]] = $productFields;
                        }
                    }
                }
                foreach ($groupingProducts as $groupingProduct) {
                    if (!array_key_exists("parent", $groupingProduct)) {
                        /**
                         * нет родилетя, он сам себе родитель
                         * создать ему родителя, и привязать
                         */
                        $parentProductId = 0;
                        foreach ($groupingProduct as $product) {
                            if (!$parentProductId && ($product["PROPERTY_ARTICLE_VALUE"] != $product["PROPERTY_PRODUCT_XML_ID"]) && $product["ACTIVE"] == "Y") {
                                /**
                                 * создать родителя привязать к себе же
                                 */
                                $productExistId = 0;
                                $products = \CIBlockElement::GetList([], ["XML_ID" => $product["PROPERTY_ARTICLE_VALUE"], "IBLOCK_ID" => 1], false, false, ["ID"]);
                                while ($gProduct = $products->GetNext()) {
                                    $productExistId = $gProduct["ID"];
                                    break;
                                }
                                if (!$productExistId) {
                                    $bqProduct = json_decode($phpMemcached->get("BQ_" . $product["PROPERTY_ARTICLE_VALUE"]), true);
                                    if ($bqProduct) {
                                        $brandQuadObject = new \Taber\BrandQuad\Utils\BrandQuadProduct($bqProduct["product"]);
                                        $parentProductId = \Taber\BrandQuad\BrandQuadProcess\ProcessProduct::constructTaberProduct($brandQuadObject);
                                    }
                                }else{
                                    $parentProductId = $productExistId;
                                }
                                if ($parentProductId) {
                                    break;
                                }
                            }
                        }
                        if ($parentProductId) {
                            foreach ($groupingProduct as $product) {
                                self::updateProductOffer($product, $parentProductId);
                            }
                            $taberProductMainFields = [
                                'IBLOCK_ID' => ProcessProduct::PRODUCT_IBLOCK_ID,
                                'ACTIVE' => 'Y',
                            ];
                            $taberProduct = new \CIBlockElement();
                            $taberProduct->Update($parentProductId, $taberProductMainFields);
                        }
                    }
                    if (count($groupingProduct) > 1 && array_key_exists("parent", $groupingProduct) && count($groupingProduct["parent"]) == 1) {
                        /**
                         * Есть родитель, торговых предложений несколько
                         */
                        $parentProductId = $groupingProduct["parent"][0]["PROPERTY_PRODUCT_VALUE"];
                        /**
                         * проверяем активно ли торговое предложение для текущего родителя
                         */
                        if($groupingProduct["parent"][0]["ACTIVE"] == "N"){
                            /**
                             * старый родитель:
                             * деактивируем старого родителя
                             */
                            $taberProductMainFields = [
                                'IBLOCK_ID' => ProcessProduct::PRODUCT_IBLOCK_ID,
                                'ACTIVE' => 'N',
                            ];
                            $taberProduct = new \CIBlockElement();
                            $taberProduct->Update($groupingProduct["parent"][0]["PROPERTY_PRODUCT_VALUE"], $taberProductMainFields);
                            foreach ($groupingProduct as $key => $product) {
                                if ($key != "parent" && $product["ACTIVE"] == "Y") {
                                    /**
                                     * новый родитель:
                                     * создаем родителя для первого активного торгового предложения
                                     */
                                    $productExistId = 0;
                                    $products = \CIBlockElement::GetList([], ["XML_ID" => $product["PROPERTY_ARTICLE_VALUE"], "IBLOCK_ID" => 1], false, false, ["ID"]);
                                    while ($product = $products->GetNext()) {
                                        $productExistId = $product["ID"];
                                        break;
                                    }
                                    if (!$productExistId) {
                                        $bqProduct = json_decode($phpMemcached->get("BQ_" . $product["PROPERTY_ARTICLE_VALUE"]), true);
                                        if ($bqProduct) {
                                            $brandQuadObject = new \Taber\BrandQuad\Utils\BrandQuadProduct($bqProduct["product"]);
                                            $parentProductId = \Taber\BrandQuad\BrandQuadProcess\ProcessProduct::constructTaberProduct($brandQuadObject);
                                        }
                                    }else{
                                        $parentProductId = $productExistId;
                                    }
                                    if($parentProductId) {
                                        break;
                                    }
                                }
                            }
                        }
                        if ($parentProductId) {
                            foreach ($groupingProduct as $key => $product) {
                                if ($key != "parent" && $product["PROPERTY_PRODUCT_VALUE"] != $parentProductId) {
                                    self::updateProductOffer($product, $parentProductId);
                                }
                            }
                            $taberProductMainFields = [
                                'IBLOCK_ID' => ProcessProduct::PRODUCT_IBLOCK_ID,
                                'ACTIVE' => 'Y',
                            ];
                            $taberProduct = new \CIBlockElement();
                            $taberProduct->Update($parentProductId, $taberProductMainFields);
                        }
                    }
                    if (array_key_exists("parent", $groupingProduct) && count($groupingProduct["parent"]) > 1) {
                        /**
                         * товары разьединились на несколько групп, их надо привязать к одному родителю(первому в списке)
                         * других родителей деактивировать
                         */
                        $parentProductId = $groupingProduct["parent"][0]["PROPERTY_PRODUCT_VALUE"];
                        foreach ($groupingProduct as $key => $product) {
                            if ($key != "parent" && $product["PROPERTY_PRODUCT_VALUE"] != $parentProductId) {
                                self::updateProductOffer($product, $parentProductId);
                            } elseif ($key == "parent") {
                                foreach ($product as $parentKey => $parentProd) {
                                    if ($parentKey != 0) {
                                        /**
                                         * деактивируем всех лишних родителей
                                         */
                                        $taberProduct = new \CIBlockElement();
                                        $taberProduct->Update($parentProd["PROPERTY_PRODUCT_VALUE"], [
                                            'IBLOCK_ID' => ProcessProduct::PRODUCT_IBLOCK_ID,
                                            'ACTIVE' => 'N',
                                        ]);
                                        $taberProduct->SetPropertyValuesEx($parentProd["ID"], ProcessProductOffer::OFFER_IBLOCK_ID, [
                                            'PRODUCT' => $parentProductId
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            self::writeDebugToFile('--grouping-complete--');
            self::writeDebugToFile('--offers--');
            $products = \CIBlockElement::GetList([], ["IBLOCK_ID" => ProcessProductOffer::OFFER_IBLOCK_ID],
                false, false, ["ID"]);
            while ($product = $products->GetNextElement()) {
                $productFields = $product->GetFields();
                $catalogProductFields["ID"] = $productFields["ID"];
                $catalogProductFields["TYPE"] = ProcessProductOffer::OFFER_TYPE;
                $catalogProduct = new \CCatalogProduct();
                $catalogProduct->add($catalogProductFields);
            }
            self::writeDebugToFile('--offers-complete--');
        }
    }

    /**
     * пишем логи в файл
     * @param $str
     */
    public function writeDebugToFile($str)
    {
        Debug::writeToFile(date("d.m.Y H:i:s"), '', '/_log/BQ/bq_v2_' . date("d.m.Y") . '.txt');
        Debug::writeToFile($str, '', '/_log/BQ/bq_v2_' . date("d.m.Y") . '.txt');
    }

    /**
     * @param $product
     * @param $parentProductId
     * @return array
     */
    private function updateProductOffer($product, $parentProductId)
    {
        $taberProduct = new \CIBlockElement();
        $taberProduct->SetPropertyValuesEx($product["ID"], ProcessProductOffer::OFFER_IBLOCK_ID, [
            'PRODUCT' => $parentProductId
        ]);
        $catalogProduct = new \CCatalogProduct();
        $catalogProductFields = [
            'ID' => $product["ID"],
            'TYPE' => ProcessProductOffer::OFFER_TYPE,
        ];
        $catalogProduct->add($catalogProductFields);
    }
}