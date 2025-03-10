<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * AuthorizationCodesController
 */
class AuthorizationCodesController extends Controller
{
    public function __construct(private ServiceInterface $service)
    {
        parent::__construct($service, "authorization-code/");   
    }

    /**
     * @param mixed $id Entity id
     * @param array $params fields
     * @param array $options
     */
    public function get($id, array $params = [], array $options = [])
    {
        return self::curl("GET", "$id/", $params, @$options["headers"] ?? [])["data"];
    }

    /**
     * @param string $code Authorization code
     * @param array $options
     */
    public function exchange($code, array $options = [])
    {
        return self::curl("POST", "exchange", ["code" => $code], @$options["headers"] ?? []);
    }
}