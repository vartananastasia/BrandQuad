<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 15.01.2019
 * Time: 12:07
 */

namespace Taber\BrandQuad\BrandQuadProcess;

use Taber\BrandQuad\Utils\BrandQuadObject;

/**
 * Первый в цепочке обработки
 * Получает продукт из BQ и проверяет есть ли он у нас в БД в Торговых предложениях
 *
 * Далее передает ответственность
 * CheckIfValidProduct на проверку наличия всех обязательных полей
 *
 * Class CheckIfExistingProduct
 * @package Taber\BrandQuad\BrandQuadProcess
 */
class CheckIfExistingProduct implements ProcessCatalog
{
    public static function process(BrandQuadObject &$brandQuadObject)
    {
        $brandQuadObject->isNew(self::checkIsNew($brandQuadObject->article()));
        CheckIfValidProduct::process($brandQuadObject);
    }

    private function checkIsNew($article)
    {
        $productId = null;
        $products = \CIBlockElement::GetList([], ["XML_ID" => $article, "IBLOCK_ID" => 12], false, false, ["ID"]);
        while ($product = $products->GetNext()) {
            $productId = $product["ID"];
            break;
        }
        return $productId;
    }
}