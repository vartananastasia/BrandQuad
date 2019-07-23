<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 15.01.2019
 * Time: 12:08
 */

namespace Taber\BrandQuad\BrandQuadProcess;

use Taber\BrandQuad\Utils\BrandQuadObject;

/**
 * Второй после CheckIfExistingProduct в цепочке обработки
 * Проверяет все ли заполнено в BQ по продукту
 *
 * Далее передает ответственность
 * либо
 * ProcessProduct если не хватает обязательных полей
 * либо
 * CheckIfUpdated если все поля есть и товар есть у нас в списке Торговых предложений
 *
 * Class CheckIfValidProduct
 * @package Taber\BrandQuad\BrandQuadProcess
 */
class CheckIfValidProduct implements ProcessCatalog
{
    public static function process(BrandQuadObject &$brandQuadObject)
    {
        /**
         * Чтобы товар загружать, он должен быть валиден
         * товар валиден тогда, когда у него есть
         * 1) хотя бы 1 фото
         * 2) категория(это наши разделы)
         * 3) и поле Проверено в BQ = да
         * 4) должен иметь ненулевые габариты
         */
        $brandQuadObject->isValid(self::checkIsValid($brandQuadObject));
        if ($brandQuadObject->isValid() && $brandQuadObject->isNew()) {
            FindProductParent::process($brandQuadObject);
        } else {
            ProcessProduct::process($brandQuadObject);
        }
    }

    private function checkIsValid($brandQuadObject)
    {
        if($brandQuadObject->checked() == "Да" && $brandQuadObject->category() && count($brandQuadObject->photo()) == 1 &&
         $brandQuadObject->weight() && $brandQuadObject->height() && $brandQuadObject->width() && $brandQuadObject->length()){
            return true;
        }else{
            return false;
        }
    }
}