<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 11.01.2019
 * Time: 11:03
 */

namespace Taber\BrandQuad\Methods;


interface BrandQuadMethod
{
    public function url();
    public function requestType(): string;
}