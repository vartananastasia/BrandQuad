<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 15.01.2019
 * Time: 12:09
 */

namespace Taber\BrandQuad\BrandQuadProcess;

use Taber\BrandQuad\Utils\BrandQuadObject;

/**
 * Третий после CheckIfValidProduct в цепочке обработки
 * Сюда приходит товар если все поля товара корректны и заполнены
 * Тут для него ищется родительский товар
 *
 * Далее передает ответственность
 * либо
 * ProcessProduct если родителя нет
 * либо
 * ProcessProductOffer если родитель есть
 *
 * Class FindProductParent
 * @package Taber\BrandQuad\BrandQuadProcess
 */
class FindProductParent implements ProcessCatalog
{
    public static function process(BrandQuadObject &$brandQuadObject)
    {
        $brandQuadObject->isParent(self::checkIsParent($brandQuadObject->article()));
        CheckIfUpdated::process($brandQuadObject);
    }

    private function checkIsParent($article)
    {
        $productId = null;  // TODO: убрать номер инфоблока
        $products = \CIBlockElement::GetList([], ["PROPERTY_ARTICLE" => $article, "IBLOCK_ID" => 1], false, false, ["ID"]);
        while ($product = $products->GetNext()) {
            $productId = $product["ID"];
            break;
        }
        return $productId;
    }
}