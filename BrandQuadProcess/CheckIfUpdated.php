<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 15.01.2019
 * Time: 17:03
 */

namespace Taber\BrandQuad\BrandQuadProcess;


use Taber\BrandQuad\Utils\BrandQuadObject;
use Taber\BrandQuad\Utils\BrandQuadProduct;
use Taber\BrandQuad\Utils\TaberProduct;

/**
 * Третий после CheckIfValidProduct в цепочке обработки
 *
 * Далее передает ответственность
 * либо
 * ProcessProduct если в товаре были внесены правки в BQ
 *
 * Class CheckIfUpdated
 * @package Taber\BrandQuad\BrandQuadProcess
 */
class CheckIfUpdated implements ProcessCatalog
{
    public static function process(BrandQuadObject &$brandQuadObject)
    {
        $brandQuadObject->isUpdated(self::checkIsUpdated($brandQuadObject));
        /**
         * отправляем товар на апдейт, чтобы записать дату последней загрузки и в мемкеш
         * даже если он не был изменен в BQ
         */
        ProcessProduct::process($brandQuadObject);
    }

    private function checkIsUpdated($brandQuadObject)
    {
        /**
         * проверяем изменился ли товар в BQ
         */
        $taberProduct = new TaberProduct($brandQuadObject->isNew());
        $updated = self::compareProducts($taberProduct, $brandQuadObject);
        return $updated;
    }

    public function compareProducts(TaberProduct $taberProduct, BrandQuadProduct $brandQuadObject)
    {
        $updated = false;
        /**
         * не пишутся к нам:
         * $brandQuadObject->findWords()
         *
         * все остальное проверяем на совпадение:
         */

        if ($taberProduct->name() != $brandQuadObject->name()) {
            $brandQuadObject->updateDiff('name', $brandQuadObject->name());
            $updated = true;
        }
        if ($taberProduct->category() != $brandQuadObject->category()) {
            $brandQuadObject->updateDiff('category', $brandQuadObject->category());
            $updated = true;
        }
        $brandQuadPhoto = $brandQuadObject->photo();
        if (current($brandQuadPhoto) != $taberProduct->photo()) {
            $brandQuadObject->updateDiff('photo', $brandQuadPhoto);
            $updated = true;
        }
        if (current($brandQuadPhoto) != $taberProduct->productPhoto()) {
            $brandQuadObject->updateDiff('productPhoto', $brandQuadPhoto);
            $updated = true;
        }
        if ($brandQuadObject->weight() != $taberProduct->weight()) {
            $brandQuadObject->updateDiff('weight', $brandQuadObject->weight());
            $updated = true;
        }
        if ($brandQuadObject->video() != $taberProduct->video()) {
            $brandQuadObject->updateDiff('video', $brandQuadObject->video());
            $updated = true;
        }
        if ($brandQuadObject->tonePhoto() != $taberProduct->tonePhoto()) {
            $brandQuadObject->updateDiff('tonePhoto', $brandQuadObject->tonePhoto());
            $updated = true;
        }
        if ($brandQuadObject->detailText() != $taberProduct->detailText()) {
            $brandQuadObject->updateDiff('detailText', $brandQuadObject->detailText());
            $updated = true;
        }
        if ($brandQuadObject->barcode() != $taberProduct->barcode()) {
            $brandQuadObject->updateDiff('barcode', $brandQuadObject->barcode());
            $updated = true;
        }
        if ($brandQuadObject->applyingType() != $taberProduct->applyingType()) {
            $brandQuadObject->updateDiff('applyingType', $brandQuadObject->applyingType());
            $updated = true;
        }
        if ($brandQuadObject->ingredients() != $taberProduct->ingredients()) {
            $brandQuadObject->updateDiff('ingredients', $brandQuadObject->ingredients());
            $updated = true;
        }
        if ($brandQuadObject->brand() != $taberProduct->brand()) {
            $brandQuadObject->updateDiff('brand', $brandQuadObject->brand());
            $updated = true;
        }
        if ($brandQuadObject->line() != $taberProduct->line()) {
            $brandQuadObject->updateDiff('line', $brandQuadObject->line());
            $updated = true;
        }
        if ($brandQuadObject->country() != $taberProduct->country()) {
            $brandQuadObject->updateDiff('country', $brandQuadObject->country());
            $updated = true;
        }
        if ($brandQuadObject->gender() != $taberProduct->gender()) {
            $brandQuadObject->updateDiff('gender', $brandQuadObject->gender());
            $updated = true;
        }
        if ($brandQuadObject->areaOfUse() != $taberProduct->areaOfUse()) {
            $brandQuadObject->updateDiff('areaOfUse', $brandQuadObject->areaOfUse());
            $updated = true;
        }
        if ($brandQuadObject->age() != $taberProduct->age()) {
            $brandQuadObject->updateDiff('age', $brandQuadObject->age());
            $updated = true;
        }
        if ($brandQuadObject->eko() != $taberProduct->eko()) {
            $brandQuadObject->updateDiff('eko', $brandQuadObject->eko());
            $updated = true;
        }
        if ($brandQuadObject->skinType() != $taberProduct->skinType()) {
            $brandQuadObject->updateDiff('skinType', $brandQuadObject->skinType());
            $updated = true;
        }
        if ($brandQuadObject->bodyEffect() != $taberProduct->bodyEffect()) {
            $brandQuadObject->updateDiff('bodyEffect', $brandQuadObject->bodyEffect());
            $updated = true;
        }
        if ($brandQuadObject->spf() != $taberProduct->spf()) {
            $brandQuadObject->updateDiff('spf', $brandQuadObject->spf());
            $updated = true;
        }
        if ($brandQuadObject->texture() != $taberProduct->texture()) {
            $brandQuadObject->updateDiff('texture', $brandQuadObject->texture());
            $updated = true;
        }
        if ($brandQuadObject->hairEffect() != $taberProduct->hairEffect()) {
            $brandQuadObject->updateDiff('hairEffect', $brandQuadObject->hairEffect());
            $updated = true;
        }
        if ($brandQuadObject->hairType() != $taberProduct->hairType()) {
            $brandQuadObject->updateDiff('hairType', $brandQuadObject->hairType());
            $updated = true;
        }
        if ($brandQuadObject->faceUse() != $taberProduct->faceUse()) {
            $brandQuadObject->updateDiff('faceUse', $brandQuadObject->faceUse());
            $updated = true;
        }
        if ($brandQuadObject->BQDetail() != $taberProduct->BQDetail()) {
            $brandQuadObject->updateDiff('BQDetail', $brandQuadObject->BQDetail());
            $updated = true;
        }
        $galleryUpdated = self::galleryUpdate($taberProduct, $brandQuadObject);
        if ($galleryUpdated) {
            $updated = true;
        }
        if ($taberProduct->active() == "N"){
            $brandQuadObject->updateDiff('active', "Y");
        }
        if (!$taberProduct->activeFrom()){
            $brandQuadObject->updateDiff('active_from', "Y");
        }
        return $updated;
    }

    /**
     * сверяем галерею
     *
     * @param TaberProduct $taberProduct
     * @param BrandQuadProduct $brandQuadObject
     * @return bool
     */
    private function galleryUpdate(TaberProduct $taberProduct, BrandQuadProduct &$brandQuadObject): bool
    {
        $updated = false;
        $taberGallery = $taberProduct->galleryPhoto();
        $brandQuadGallery = $brandQuadObject->galleryPhoto();
        $galleryDiffAdd = [];
        $galleryDiffDel = [];
        /**
         * проверяем какие фото добавлены в галерею
         */
        foreach ($brandQuadGallery as $galleryPhoto) {
            if (!in_array($galleryPhoto, $taberGallery)) {
                $galleryDiffAdd[] = $galleryPhoto;
                $updated = true;
            }
        }
        if ($galleryDiffAdd) {
            $brandQuadObject->updateDiff('galleryAdd', $galleryDiffAdd);
        }
        /**
         * проверяем какие фото удалены из галереи
         */
        foreach ($taberGallery as $photoId => $galleryPhoto) {
            if (!in_array($galleryPhoto, $brandQuadGallery)) {
                $galleryDiffDel[$photoId] = $galleryPhoto;
                $updated = true;
            }
        }
        if ($galleryDiffDel) {
            $brandQuadObject->updateDiff('galleryDel', $galleryDiffDel);
        }
        return $updated;
    }
}