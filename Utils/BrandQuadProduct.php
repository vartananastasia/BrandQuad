<?php
/**
 * Created by PhpStorm.
 * User: a.vartan
 * Date: 11.01.2019
 * Time: 10:29
 */

namespace Taber\BrandQuad\Utils;


class BrandQuadProduct extends BrandQuadObject
{
    /**
     * Является ли товар новым, или уже у нас в БД есть
     * @var
     */
    private $new;
    /**
     * Все ли обязательные поля заполнены
     * @var
     */
    private $valid;
    /**
     * Есть ли у него родительский элемент
     * @var
     */
    private $parent;
    /**
     * Загружен ли товар в БД, массив ошибок загрузки
     * @var array
     */
    private $downloaded;
    /**
     * Обновлен ли товар
     * @var
     */
    private $updated;
    /**
     * @var array
     */
    private $product;

    public function __construct(array $product)
    {
        $this->product = $product;
        $this->product["taber"] = [];
        $this->new = false;
        $this->downloaded = [];
        $this->parent = false;
        $this->updated = false;
        $this->valid = false;
        /**
         * Будем записывать в кеш после полной проверки товара
         */
//        self::cacheProduct();
    }

    public function checked()
    {
        return $this->product["attributes"]["Проверено"][0];  // нет
    }

    public function photo()
    {
        /**
         * первое фото в списке (это и детальная картинка и превьюшка в Торговом предложении и в Товаре)
         * обязательно для загрузки(иначе товар к нам не грузится)
         *
         * могут прийти name фотографии без _, значит определяем его как основное фото товара,
         * могут прийти фото _2 _4 _51, значит берем первое из отсортированного массива(потому что это не всегда _1)
         */
        $mainPhoto = [];
        $photosForSort = [];
        $photo['photo']["hash"] = '';
        foreach ($this->product["assets"] as $asset) {
            if ($asset["attribute"]["name"] == "Фото для сайта") {
                if (!strpos($asset["dam"]["name"], '_')) {
                    $photo['photo'] = $asset["dam"];
                    $photo['photo']["hash"] = md5($asset["dam"]['url'] . $asset["dam"]['id'] . $asset["dam"]['name']);
                    $mainPhoto = [$asset["dam"]['url'] => $photo['photo']["hash"]];
                    break;
                } else {
                    $photosForSort[$asset["dam"]["name"]]["url"] = $asset["dam"]['url'];
                    $photosForSort[$asset["dam"]["name"]]["externalId"] = md5($asset["dam"]['url'] . $asset["dam"]['id'] . $asset["dam"]['name']);
                }
            }
        }
        /**
         * рас нет фото без _ то берем первое фото из отсортированного по имени массива
         */
        if (!$mainPhoto) {
            ksort($photosForSort);
            foreach ($photosForSort as $key => $photo) {
                $mainPhoto = [$photo["url"] => $photo["externalId"]];
                break;
            }
        }
        return $mainPhoto;  // детальное и превью фото
    }

    public function galleryPhoto()
    {
        /**
         * все фото кроме первого(это GALLERY и в Товаре и в Торговом предложении)
         * их может быть несколько или не быть вообще
         */
        $photos = [];
        $mainPhotoCut = false;
        $photosForSort = [];
        foreach ($this->product["assets"] as $key => $asset) {
            if ($asset["attribute"]["name"] == "Фото для сайта") {
                if (!strpos($asset["dam"]["name"], '_')) {
                    $mainPhotoCut = true;
                } else {
                    $photosForSort[$asset["dam"]["name"]]["url"] = $asset["dam"]['url'];
                    $photosForSort[$asset["dam"]["name"]]["externalId"] = md5($asset["dam"]['url'] . $asset["dam"]['id'] . $asset["dam"]['name']);
                }
            }
        }
        /**
         * сортируем по возрастанию _1 _2 и убираем главное фото из списка, если оно уже не убрано
         */
        ksort($photosForSort);
        if (!$mainPhotoCut) {
            $count = 1;
            foreach ($photosForSort as $key => $photo) {
                if ($count == 1) {
                    unset($photosForSort[$key]);
                } else {
                    $photos[$photo['url']] = $photo["externalId"];
                }
                $count++;
            }
        }else{
            foreach ($photosForSort as $key => $photo) {
                $photos[$photo['url']] = $photo["externalId"];
            }
        }
        return $photos;  // свойство типа файл
    }

    public function groupHash()
    {
        if (!$this->product["taber"]["groupHash"]) {
            $groupHash = '';
            if ($this->product["taber"]) {
                /**
                 * группируем по бренду, разделу, линейке и названию до слова тон
                 */
                $groupHash = md5($this->product["taber"]["Бренд"] .
                    $this->product["taber"]["Линейка"] .
                    $this->product["taber"]["category"] .
                    trim(preg_replace('/^(.+?)\тон .+$/', '\\1', $this->name())));
                new TaberGroupHash($groupHash);
            }
            $this->product["taber"]["groupHash"] = $groupHash;
        }
        return $this->product["taber"]["groupHash"];
    }

    public function tonePhoto()
    {
        $photos["tone"]["hash"] = '';
        foreach ($this->product["assets"] as $asset) {
            if ($asset["attribute"]["name"] == "Фото тона для сайта") {
                $photos["tone"] = $asset["dam"];
                $photos["tone"]["hash"] = md5($asset["dam"]['url'] . $asset["dam"]['id'] . $asset["dam"]['name']);
                $photos["tone"]["article"] = $this->article();
                break;
            }
        }
        if ($photos["tone"]["hash"]) {
            if (!$this->product["taber"]["Тон"]) {
                $tone = new TaberTone($photos["tone"]);
                $this->product["taber"]["Тон"] = $tone->xmlId();
            }
        } else {
            $this->product["taber"]["Тон"] = '';
        }
        return $this->product["taber"]["Тон"];  // справочник
    }

    public function category()
    {
        if (is_null($this->product["taber"]["category"])) {
            $sectionCompare = $this->cacheCategories();
            foreach ($this->product["categories"] as $section) {
                $this->product["taber"]["category"] = $sectionCompare[$section["id"]]["section"];
                if ($this->product["taber"]["category"]) {
                    $this->product["taber"]["grouping"] = $sectionCompare[$section["id"]]["grouping"];
                    break;
                }
                if (!$this->product["taber"]["category"]) {
                    self::addNewCategory($section);
                }
            }
            if (!$this->product["taber"]["category"]) {
                $this->product["taber"]["category"] = '';
            }
        }
        return $this->product["taber"]["category"];
    }

    private function addNewCategory($category)
    {
        global $USER;
        $USER->Authorize(1);
        $categoryId = $category["id"];
        $taberCat = [];
        $bqCat = \CIBlockSection::GetList([], ["IBLOCK_ID" => 31, "XML_ID" => $categoryId], false, ['NAME', 'ID', 'XML_ID']);
        while ($section = $bqCat->getNext()) {
            $taberCat = $section;
            break;
        }
        if(!$taberCat) {
            $phpMemcached = new \Memcached;
            $phpMemcached->addServer('memcached.internal', 11211);
            $sectionsBQ["children"] = json_decode($phpMemcached->get("BQ_sections"), true);
            if (!$sectionsBQ["children"]) {
                $brandQuadClient = new \Taber\BrandQuad\BrandQuadClient();
                $brandQuadMethod = new \Taber\BrandQuad\Methods\GetCategories();
                $brandQuadClient->executeMethod($brandQuadMethod);
                $phpMemcached->set('BQ_sections', json_encode($brandQuadClient->result()), time() + 60 * 60 * 20);
            }
            $categories = $sectionsBQ;
            $parentCategory = self::categorySort($categories, $categoryId);
            if($parentCategory) {
                $bqSections = \CIBlockSection::GetList([], ["IBLOCK_ID" => 31, "XML_ID" => $parentCategory["id"]], false, ['NAME', 'ID', 'XML_ID']);
                $parentSection = [];
                while ($section = $bqSections->getNext()) {
                    $parentSection = $section;
                    break;
                }
                if ($parentSection) {
                    $taberSection = new \CIBlockSection();
                    $taberSection->Add(["ACTIVE" => "Y", "IBLOCK_ID" => 31, "NAME" => $category["name"], "XML_ID" => $category["id"], "IBLOCK_SECTION_ID" => $parentSection["ID"]]);
                }
            }
        }
    }

    private static function categorySort($categories, $categoryId)
    {
        $parentCategory = [];
        foreach ($categories["children"] as $section) {
            if ($section["id"] == $categoryId) {
                $parentCategory = $categories;
                break;
            }
            if ($section["children"]) {
                if (!$parentCategory) {
                    $parentCategory = self::categorySort($section, $categoryId);
                }
            }
        }
        return $parentCategory;
    }

    public function isGrouping()
    {
        return $this->product["taber"]["grouping"];
    }

    public function cacheProduct()
    {
        /**
         * пишем массив данных о товаре из BQ в кеш на 3 часа
         */
        $phpMemcached = new \Memcached;
        $phpMemcached->addServer('memcached.internal', 11211);
        $phpMemcached->set('BQ_' . $this->article(), json_encode([
            "errors" => $this->isDownloaded(),
            "parent" => $this->isParent(),
            "taberId" => $this->isNew(),
            "updated" => $this->isUpdated(),
            "valid" => $this->isValid(),
            "product" => $this->product(),
        ]), time() + 60 * 60 * 20);
    }

    public static function cacheProductResult($article)
    {
        $phpMemcached = new \Memcached;
        $phpMemcached->addServer('memcached.internal', 11211);
        return json_decode($phpMemcached->get('BQ_' . $article), true);
    }

    public function isParent($parent = null)
    {
        if (!is_null($parent)) {
            $this->parent = $parent;
        }
        return $this->parent;
    }

    public function isValid($valid = null)
    {
        if (!is_null($valid) && gettype($valid) == 'boolean') {
            $this->valid = $valid;
        }
        return $this->valid;
    }

    public function isDownloaded($downloaded = null)
    {
        if (!is_null($downloaded)) {
            $this->downloaded[] = $downloaded;
        }
        return $this->downloaded;
    }

    public function isUpdated($updated = null)
    {
        if (!is_null($updated) && gettype($updated) == 'boolean') {
            $this->updated = $updated;
        }
        return $this->updated;
    }

    public function isNew($new = null)
    {
        if (!is_null($new)) {
            $this->new = $new;
        }
        return $this->new;
    }

    public function product()
    {
        return $this->product;
    }

    public function taberProductFields()
    {
        return $this->product["taber"];
    }

    public function group()
    {
        return $this->product["attributes"]["Группа"];
    }

    public function eko()
    {
        return $this->product["attributes"]["ЭКО"];
    }

    public function weight()
    {
        $this->product["attributes"]["Вес, г"][0] = trim($this->product["attributes"]["Вес, г"][0]);
        return $this->product["attributes"]["Вес, г"][0];
    }

    public function height()
    {
        $this->product["attributes"]["Высота, мм"][0] = trim($this->product["attributes"]["Высота, мм"][0]);
        return $this->product["attributes"]["Высота, мм"][0];
    }

    public function width()
    {
        $this->product["attributes"]["Ширина, мм"][0] = trim($this->product["attributes"]["Ширина, мм"][0]);
        return $this->product["attributes"]["Ширина, мм"][0];
    }

    public function length()
    {
        $this->product["attributes"]["Длина, мм"][0] = trim($this->product["attributes"]["Длина, мм"][0]);
        return $this->product["attributes"]["Длина, мм"][0];
    }

    public function country()
    {
        $this->product["attributes"]["Страна Производства"][0] = trim($this->product["attributes"]["Страна Производства"][0]);
        if ($this->product["attributes"]["Страна Производства"][0]) {
            if (!$this->product["taber"]["Страна Производства"]) {
                $country = new TaberCountry($this->product["attributes"]["Страна Производства"][0]);
                $this->product["taber"]["Страна Производства"] = $country->xmlId();
            }
        } else {
            $this->product["taber"]["Страна Производства"] = '';
        }
        return $this->product["taber"]["Страна Производства"];  // справочник
    }

    public function madeBy()
    {
        $this->product["attributes"]["Производитель"][0] = trim($this->product["attributes"]["Производитель"][0]);
        return $this->product["attributes"]["Производитель"][0];  // нет
    }

    public function BQDetail()
    {
        $this->product["attributes"]["Детализация"][0] = trim(mb_convert_encoding($this->product["attributes"]["Детализация"][0], "UTF-8"));
        if ($this->product["attributes"]["Детализация"][0]) {
            if (!$this->product["taber"]["Детализация"]) {
                $taberBQDetail = new TaberBQDetail($this->product["attributes"]["Детализация"][0]);
                $this->product["taber"]["Детализация"] = $taberBQDetail->xmlId();
            }
        } else {
            $this->product["taber"]["Детализация"] = '';
        }
        return $this->product["taber"]["Детализация"];  // справочник
    }

    public function updateDiff($fieldName, $fieldValue)
    {
        $this->product["diff"][$fieldName] = $fieldValue;
    }

    public function checkDiff($fieldName)
    {
        if (array_key_exists($fieldName, $this->product["diff"])) {
            return true;
        } else {
            return false;
        }
    }

    public function diff()
    {
        return $this->product["diff"];
    }

    public function subcategory()
    {
        $this->product["attributes"]["Подкатегория"] = trim($this->product["attributes"]["Подкатегория"]);
        return $this->product["attributes"]["Подкатегория"];
    }

    public function article()
    {
        return $this->product["attributes"]["Артикул Подружки"];  // строка
    }

    public function line()
    {
        $this->product["attributes"]["Линейка"][0] = trim($this->product["attributes"]["Линейка"][0]);
        if ($this->product["attributes"]["Линейка"][0]) {
            if (!$this->product["taber"]["Линейка"]) {
                $taberLine = new TaberLine($this->product["attributes"]["Линейка"][0], $this->brand());
                $this->product["taber"]["Линейка"] = $taberLine->xmlId();
            }
        } else {
            $this->product["taber"]["Линейка"] = '';
        }
        return $this->product["taber"]["Линейка"];  // справочник
    }

    public function subbrand()
    {
        return $this->product["attributes"]["Подбренд"];  // нет
    }

    public function brand()
    {
        $this->product["attributes"]["Бренд"][0] = trim($this->product["attributes"]["Бренд"][0]);
        if ($this->product["attributes"]["Бренд"][0]) {
            if (!$this->product["taber"]["Бренд"]) {
                $brand = new TaberBrand($this->product["attributes"]["Бренд"][0]);
                $this->product["taber"]["Бренд"] = $brand->id();
            }
        } else {
            $this->product["taber"]["Бренд"] = 0;
        }
        return $this->product["taber"]["Бренд"];  // инфоблок
    }

    public function name()
    {
        $this->product["attributes"]["Название"] = trim($this->product["attributes"]["Название"]);
        return $this->product["attributes"]["Название"];  // строка
    }

    public function barcode()
    {
        $this->product["attributes"]["EAN производителя"][0] = trim($this->product["attributes"]["EAN производителя"][0]);
        return $this->product["attributes"]["EAN производителя"][0];  // строка
    }

    public function video()
    {
        $this->product["attributes"]["Видео для сайта"][0] = trim($this->product["attributes"]["Видео для сайта"][0]);
        return $this->product["attributes"]["Видео для сайта"][0];  // строка
    }

    public function findWords()
    {
        $this->product["attributes"]["Поисковое слово"] = trim($this->product["attributes"]["Поисковое слово"]);
        return $this->product["attributes"]["Поисковое слово"];  // нет
    }

    public function faceUse()
    {
        $this->product["attributes"]["Применение для лица"][0] = trim($this->product["attributes"]["Применение для лица"][0]);
        if ($this->product["attributes"]["Применение для лица"][0]) {
            if (!$this->product["taber"]["Применение для лица"]) {
                $taberFaceUse = new TaberFaceUse($this->product["attributes"]["Применение для лица"][0]);
                $this->product["taber"]["Применение для лица"] = $taberFaceUse->xmlId();
            }
        } else {
            $this->product["taber"]["Применение для лица"] = '';
        }
        return $this->product["taber"]["Применение для лица"];  // справочник
    }

    public function bodyEffect()
    {
        $this->product["attributes"]["Эффект для лица и тела"][0] = trim($this->product["attributes"]["Эффект для лица и тела"][0]);
        if ($this->product["attributes"]["Эффект для лица и тела"][0]) {
            if (!$this->product["taber"]["Эффект для лица и тела"]) {
                $taberBodyEffect = new TaberBodyEffect($this->product["attributes"]["Эффект для лица и тела"][0]);
                $this->product["taber"]["Эффект для лица и тела"] = $taberBodyEffect->xmlId();
            }
        } else {
            $this->product["taber"]["Эффект для лица и тела"] = '';
        }
        return $this->product["taber"]["Эффект для лица и тела"];  // справочник
    }

    public function hairEffect()
    {
        $this->product["attributes"]["Эффект для волос"][0] = trim($this->product["attributes"]["Эффект для волос"][0]);
        if ($this->product["attributes"]["Эффект для волос"][0]) {
            if (!$this->product["taber"]["Эффект для волос"]) {
                $taberHairEffect = new TaberHairEffect($this->product["attributes"]["Эффект для волос"][0]);
                $this->product["taber"]["Эффект для волос"] = $taberHairEffect->xmlId();
            }
        } else {
            $this->product["taber"]["Эффект для волос"] = '';
        }
        return $this->product["taber"]["Эффект для волос"];  // справочник
    }

    public function spf()
    {
        $this->product["attributes"]["SPF"][0] = trim($this->product["attributes"]["SPF"][0]);
        if ($this->product["attributes"]["SPF"][0]) {
            if (!$this->product["taber"]["SPF"]) {
                $spf = new TaberBodyEffect($this->product["attributes"]["SPF"][0]);
                $this->product["taber"]["SPF"] = $spf->xmlId();
            }
        } else {
            $this->product["taber"]["SPF"] = '';
        }
        return $this->product["taber"]["SPF"];  // справочник
    }

    public function texture()
    {
        $this->product["attributes"]["Текстура"][0] = trim($this->product["attributes"]["Текстура"][0]);
        if ($this->product["attributes"]["Текстура"][0]) {
            if (!$this->product["taber"]["Текстура"]) {
                $texture = new TaberTexture($this->product["attributes"]["Текстура"][0]);
                $this->product["taber"]["Текстура"] = $texture->xmlId();
            }
        } else {
            $this->product["taber"]["Текстура"] = '';
        }
        return $this->product["taber"]["Текстура"];  // справочник
    }

    public function age()
    {
        $this->product["attributes"]["Возраст"][0] = trim($this->product["attributes"]["Возраст"][0]);
        if ($this->product["attributes"]["Возраст"][0]) {
            if (!$this->product["taber"]["Возраст"]) {
                $age = new TaberAge($this->product["attributes"]["Возраст"][0]);
                $this->product["taber"]["Возраст"] = $age->xmlId();
            }
        } else {
            $this->product["taber"]["Возраст"] = '';
        }
        return $this->product["taber"]["Возраст"];  // справочник
    }

    public function hairType()
    {
        $this->product["attributes"]["Тип волос"][0] = trim($this->product["attributes"]["Тип волос"][0]);
        if ($this->product["attributes"]["Тип волос"][0]) {
            if (!$this->product["taber"]["Тип волос"]) {
                $hairType = new TaberHairType($this->product["attributes"]["Тип волос"][0]);
                $this->product["taber"]["Тип волос"] = $hairType->xmlId();
            }
        } else {
            $this->product["taber"]["Тип волос"] = '';
        }
        return $this->product["taber"]["Тип волос"];  // справочник
    }

    public function skinType()
    {
        $this->product["attributes"]["Тип кожи"][0] = trim($this->product["attributes"]["Тип кожи"][0]);
        if ($this->product["attributes"]["Тип кожи"][0]) {
            if (!$this->product["taber"]["Тип кожи"]) {
                $skinType = new TaberSkinType($this->product["attributes"]["Тип кожи"][0]);
                $this->product["taber"]["Тип кожи"] = $skinType->xmlId();
            }
        } else {
            $this->product["taber"]["Тип кожи"] = '';
        }
        return $this->product["taber"]["Тип кожи"];  // справочник
    }

    public function areaOfUse()
    {
        $this->product["attributes"]["Область использования"][0] = trim($this->product["attributes"]["Область использования"][0]);
        if ($this->product["attributes"]["Область использования"][0]) {
            if (!$this->product["taber"]["Область использования"]) {
                $areaOfUse = new TaberAreaOfUse($this->product["attributes"]["Область использования"][0]);
                $this->product["taber"]["Область использования"] = $areaOfUse->xmlId();
            }
        } else {
            $this->product["taber"]["Область использования"] = '';
        }
        return $this->product["taber"]["Область использования"];  // справочник
    }

    public function color()
    {
        return $this->product["attributes"]["Цвет"];  // нет
    }

    public function detailText()
    {
        return $this->product["attributes"]["Подробное описание"][0];  // строка
    }

    public function ingredients()
    {
        return $this->product["attributes"]["Состав"][0];  // строка
    }

    public function applyingType()
    {
        return $this->product["attributes"]["Способ использования и нанесения"][0];  // строка
    }

    public function gender()
    {
        $this->product["attributes"]["Пол"][0] = trim($this->product["attributes"]["Пол"][0]);
        if ($this->product["attributes"]["Пол"][0]) {
            if (!$this->product["taber"]["Пол"]) {
                $gender = new TaberGender($this->product["attributes"]["Пол"][0]);
                $this->product["taber"]["Пол"] = $gender->xmlId();
            }
        } else {
            $this->product["taber"]["Пол"] = '';
        }
        return $this->product["taber"]["Пол"];  // справочник
    }

    /**
     * @return array
     */
    public static function cacheCategories(): array
    {
        //  TODO: перенести в другой класс
        $phpMemcached = new \Memcached;
        $phpMemcached->addServer('memcached.internal', 11211);
        $sectionCompare = json_decode($phpMemcached->get("BQ_category"), true);
        if (!$sectionCompare) {
            global $USER;
            $USER->Authorize(1);
            $sectionCompare = [];
            $bqTaberSection = [];
            $sections = \CIBlockSection::GetList([], ["IBLOCK_ID" => 1], false, ['NAME', 'ID', 'UF_BQ_SECTION_LINK', "UF_LINE_GROUP"]);
            while ($section = $sections->getNext()) {
                foreach ($section["UF_BQ_SECTION_LINK"] as $bqSection) {
                    $bqTaberSection[$bqSection] = ["section" => $section["ID"], "grouping" => $section["UF_LINE_GROUP"]];
                }
            }
            $bqSections = \CIBlockSection::GetList([], ["IBLOCK_ID" => 31], false, ['NAME', 'ID', 'XML_ID']);
            while ($section = $bqSections->getNext()) {  // доступно только админам
                $sectionCompare[$section["XML_ID"]] = ["section" => $bqTaberSection[$section["ID"]]["section"],
                    "grouping" => $bqTaberSection[$section["ID"]]["grouping"]];
            }

            $phpMemcached->set('BQ_category', json_encode($sectionCompare), time() + 60 * 60 * 20);
        }
        return $sectionCompare;
    }
}