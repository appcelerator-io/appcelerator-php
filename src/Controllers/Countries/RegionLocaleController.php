<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Countries;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * RegionLocaleController
 */
class RegionLocaleController extends Controller
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "region-locale/", $opts);
    }

    /**
     * @param string $regionCode Region code (ISO 3166-1 alpha-2)
     * @param string $locale Locale code e.g. en, fr
     * @param array $options
     */
    public function get($regionCode, $locale = null, array $params = [], array $options = [])
    {
        return self::curl("GET", "$regionCode/", array_merge(["locale" => $locale], $params), @$options["headers"] ?? [])["data"];
    }

    /**
     * @param string $search Partial region name
     * @param string $locale Locale code e.g. en, fr
     * @param array $params
     * @param array $options
     */
    public function suggest($search, $locale = null, array $params = [], array $options = [])
    {
        return self::curl("GET", "suggest", array_merge(['search' => $search, 'locale' => $locale], $params), @$options["headers"] ?? [])["data"];
    }
}