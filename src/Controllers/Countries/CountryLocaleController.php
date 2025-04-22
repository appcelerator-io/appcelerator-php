<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Countries;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * CountryLocaleController
 */
class CountryLocaleController extends Controller
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "country-locale/", $opts);   
    }

    /**
     * @param string $countryCode Country code (ISO 3166-1 alpha-2)
     * @param string $locale Locale code e.g. en, fr
     * @param array $options
     */
    public function get($countryCode, $locale = null, array $params = [], array $options = [])
    {
        return self::curl("GET", "$countryCode/", array_merge(["locale" => $locale], $params), @$options["headers"] ?? [])["data"];
    }

    /**
     * @param string $search Partial country name
     * @param string $locale Locale code e.g. en, fr
     * @param array $params
     * @param array $options
     */
    public function suggest($search, $locale = null, array $params = [], array $options = [])
    {
        return self::curl("GET", "suggest", array_merge(['search' => $search, 'locale' => $locale], $params), @$options["headers"] ?? [])["data"];
    }
}