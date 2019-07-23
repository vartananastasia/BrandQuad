<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 11.01.2019
 * Time: 10:43
 */

namespace Taber\BrandQuad\Methods;


use Taber\BrandQuad\BrandQuadClient;

class GetCategories implements BrandQuadMethod
{
    const METHOD_REQUEST_TYPE = 'GET';
    const METHOD_REQUEST_URL = 'categories/';

    public function requestType(): string
    {
        return self::METHOD_REQUEST_TYPE;
    }

    public function url()
    {
        return BrandQuadClient::BRAND_QUAD_BASE_URL . self::METHOD_REQUEST_URL;
    }

}