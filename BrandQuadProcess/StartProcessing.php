<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 15.01.2019
 * Time: 14:27
 */

namespace Taber\BrandQuad\BrandQuadProcess;


use Taber\BrandQuad\Utils\BrandQuadObject;

class StartProcessing implements ProcessCatalog
{
    public static function process(BrandQuadObject &$brandQuadObject)
    {
        CheckIfExistingProduct::process($brandQuadObject);
    }
}