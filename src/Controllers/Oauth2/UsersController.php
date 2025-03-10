<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\OAuth2;

use AppCelerator\Controllers\CRUDController;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * UsersController
 */
class UsersController extends CRUDController
{
    public function __construct(private ServiceInterface $service)
    {
        parent::__construct($service, "user/");   
    }

    /**
     * Retrieve user data based on the provided token
     * Use options['headers']['Authorization'] to overwrite the default token
     * @param array $params - fields
     */
    public function self(array $params = [], array $options = [])
    {
        return self::curl("GET", "self", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $email User email
     * @param string $password User password
     * @param array $options
     */
    public function create($email, $password, $params = [], array $options = [])
    {
        return self::curl("POST", "", array_merge(["email" => $email, "password" => $password], $params), @$options["headers"] ?? []);
    }

    /**
     * @param string $email User email
     * @param string $password User password
     * @param array $options: scopes => [scope1,...]
     * @return array [tokenData]
     */
    public function authenticate($email, $password, array $params = [], array $options = [])
    {
        $params = array_merge([
            "email" => $email,
            "password" => $password,
        ], $params);

        return self::curl("POST", "authenticate", $params, @$options["headers"] ?? []);
    }

    /**
     * @param string $userId User ID 
     * @param string $email User email
     * @param array $options
     * @return array [emailConfirmToken]
     */
    public function updateEmail($userId, $email, array $options = [])
    {
        return self::curl("POST", "$userId/email", ["email" => $email], @$options["headers"] ?? []);
    }

    /**
     * @param string $userId User ID 
     * @param string $emailConfirmToken User emailConfirmToken
     * @param array $options
     * @return array [code]
     */
    public function confirmEmail($userId, $emailConfirmToken = "", array $options = [])
    {
        return self::curl("POST", "$userId/confirm-email", ["emailConfirmToken" => $emailConfirmToken], @$options["headers"] ?? []);
    }

    /**
     * @param string $userId User ID 
     * @param string $password User password
     * @param string $passwordRepeat Repeat password
     * @param string $currentPassword Current password
     * @param array $options
     * @return array [code]
     */
    public function updatePassword($userId, $password, $passwordRepeat, $currentPassword, array $options = [])
    {
        return self::curl("POST", "$userId/password", ["password" => $password, "passwordRepeat" => $passwordRepeat, "currentPassword" => $currentPassword], @$options["headers"] ?? []);
    }

    /**
     * @param string $email User email
     * @param array $options
     * @return array [userId,identifier,token]
     */
    public function requestPasswordReset($email, array $options = [])
    {
        return self::curl("POST", "request-password-reset", ["email" => $email], @$options["headers"] ?? []);
    }

    /**
     * @param string $email User email
     * @param array $options
     * @return array [userId,identifier,token]
     */
    public function resetPassword($identifier, $token, $password, array $options = [])
    {
        return self::curl("POST", "reset-password", ["identifier" => $identifier, "token" => $token, "password" => $password], @$options["headers"] ?? []);
    }

    /**
     * @param array $params
     * @param array $options
     * @return array
     */
    public function logs(array $params = [], array $options = [])
    {
        return self::curl("GET", "logs", $params, @$options["headers"] ?? []);
    }
}