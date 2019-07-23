<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 11.01.2019
 * Time: 10:43
 */

namespace Taber\BrandQuad\Methods;


use Taber\BrandQuad\BrandQuadClient;

class GetProducts implements BrandQuadMethod
{
    private $productsPageLimit;
    private $productsPage;
    const METHOD_REQUEST_TYPE = 'GET';
    const METHOD_REQUEST_URL = 'products/';
    const PRODUCTS_PAGE_LIMIT = 100;
    const PRODUCTS_PAGE = 1;

    public function __construct()
    {
        $this->productsPage = self::PRODUCTS_PAGE;
        $this->productsPageLimit = self::PRODUCTS_PAGE_LIMIT;
    }

    public function resetProductsPage($productsPage)
    {
        $this->productsPage = $productsPage;
    }

    public function page()
    {
        return $this->productsPage;
    }

    public function resetProductsPageLimit($productPageLimit)
    {
        $this->productsPageLimit = $productPageLimit;
    }

    private function params()
    {
        return '?page=' . $this->productsPage . '&page_size=' . $this->productsPageLimit;
    }

    public function requestType(): string
    {
        return self::METHOD_REQUEST_TYPE;
    }

    public function url()
    {
        return BrandQuadClient::BRAND_QUAD_BASE_URL . self::METHOD_REQUEST_URL . $this->params();
    }
}