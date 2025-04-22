<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Countries;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * CountriesController
 */
class CountriesController extends Controller
{
    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "country/", $opts);
    }

    /**
     * @param mixed $id Entity id
     * @param string $locale Locale code e.g. en, fr
     * @param array $params fields
     * @param array $options
     */
    public function get($id, $locale = null, array $params = [], array $options = [])
    {
        return self::curl("GET", "$id/", array_merge(['locale' => $locale], $params), @$options["headers"] ?? [])["data"];
    }

    /**
     * @param string $locale Locale code e.g. en, fr
     * @param array $params fields, orderBy <name>, orderByDirection <asc,desc>, limit, offset
     * @param array $options
     */
    public function list($locale = null, array $params = [], array $options = [])
    {
        return self::curl("GET", "", array_merge(['locale' => $locale], $params), @$options["headers"] ?? [])["data"];
    }

    /**
     * @param array $params
     * @param array $options
     */
    public function dialCodes(array $params = [], array $options = [])
    {
        return self::curl("GET", "dial-codes", $params, @$options["headers"] ?? [])["data"];
    }
}