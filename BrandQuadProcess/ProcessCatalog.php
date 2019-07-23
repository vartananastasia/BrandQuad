<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 11.01.2019
 * Time: 14:18
 */

namespace Taber\BrandQuad\BrandQuadProcess;


use Taber\BrandQuad\Utils\BrandQuadObject;

interface ProcessCatalog
{
    public static function process(BrandQuadObject &$brandQuadObject);
}