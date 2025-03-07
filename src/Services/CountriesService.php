<?php
declare(strict_types=1);

namespace AppCelerator\Services;

use AppCelerator\Client;

/**
 * CountriesService
 * 
 * @property \AppCelerator\Controllers\Countries\CountriesController $countries
 * @property \AppCelerator\Controllers\Countries\CountryLocaleController $countryLocale
 * @property \AppCelerator\Controllers\Countries\RegionLocaleController $regionLocale
 * @property \AppCelerator\Controllers\Countries\LocaleController $locale
 */
class CountriesService extends Service
{
    public function __construct(private Client $client)
    {
        parent::__construct($client, "Countries");
    }
}