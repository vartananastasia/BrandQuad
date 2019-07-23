<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 10.01.2019
 * Time: 10:29
 */

namespace Taber\BrandQuad;


use Taber\BrandQuad\Methods\BrandQuadMethod;
use Taber\BrandQuad\Methods\GetCategories;
use Taber\BrandQuad\Methods\GetProducts;
use Taber\BrandQuad\Utils\BrandQuadProduct;

class BrandQuadClient
{
    private $requestHeaders = [];
    private $result = [];
    private $nextUrl;

    const BRAND_QUAD_TOKEN = 'GBB5******IJJ';
    const BRAND_QUAD_APPID = 'D****G';
    const BRAND_QUAD_BASE_URL = 'https://podrygka.brandquad.ru/api/public_v2/';

    public function __construct()
    {
        $this->requestHeaders = [
            'TOKEN' => self::BRAND_QUAD_TOKEN,
            'APPID' => self::BRAND_QUAD_APPID
        ];
    }

    public function executeMethod(BrandQuadMethod $method)
    {
        $this->nextUrl = $method->url();
        $this->result = [];
        $client = new \GuzzleHttp\Client();
        $res = $client->request($method->requestType(), $this->nextUrl, [
            'headers' => $this->requestHeaders
        ]);
        $responseBody = json_decode($res->getBody(), true);
        foreach ($responseBody["results"] as $brandQuadObject) {
            if ($method instanceof GetProducts) {
                $this->result[] = new BrandQuadProduct($brandQuadObject);
            }elseif ($method instanceof GetCategories){
                $this->result = $responseBody["results"];
                break;
            }
        }
        $this->nextUrl = $responseBody["next"] ?? '';
    }

    public function nextUrl()
    {
        return $this->nextUrl;
    }

    public function result()
    {
        return $this->result;
    }
}