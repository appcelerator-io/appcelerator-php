<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Countries;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * LocaleController
 */
class LocaleController extends Controller
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "locale/", $opts);
    }

    /**
     * @param string $locale Locale code e.g. en, fr
     * @param string $countryCodes Country code comma separated (ISO 3166-1 alpha-2)
     * @param string $regionCodes Region code comma separated (ISO 3166-1 alpha-2)
     * @param array $options
     */
    public function locale($locale, $countryCodes = null, $regionCodes = null, array $params = [], array $options = [])
    {
        return self::curl("GET", "$locale/", array_merge(["countryCodes" => $countryCodes, "regionCodes" => $regionCodes], $params), @$options["headers"] ?? [])["data"];
    }
}