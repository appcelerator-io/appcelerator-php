<?php
declare(strict_types=1);

namespace AppCelerator\Controllers\Oauth2;

use AppCelerator\Controllers\Controller;
use AppCelerator\Interfaces\ServiceInterface;

/**
 * TokenController
 *  The OAuth2 token endpoint, one method per grant type. The scope parameter is a space delimited list,
 *  add openid, profile and email for an id_token, offline_access for a refresh token.
 */
class TokenController extends Controller
{
    public const string GRANT_TYPE_CLIENT_CREDENTIALS = "client_credentials";
    public const string GRANT_TYPE_PASSWORD = "password";
    public const string GRANT_TYPE_AUTHORIZATION_CODE = "authorization_code";
    public const string GRANT_TYPE_REFRESH_TOKEN = "refresh_token";
    public const string GRANT_TYPE_MFA_OTP = "https://appcelerator.io/oauth/grant-type/mfa-otp";
    public const string GRANT_TYPE_JWT_BEARER = "urn:ietf:params:oauth:grant-type:jwt-bearer";

    public function __construct(private ServiceInterface $service, array $opts = [])
    {
        parent::__construct($service, "token", $opts);
    }

    /**
     * Raw token request
     * @param array $params grant_type, client_id and the parameters of the grant type
     * @param array $options
     * @return array [access_token, token_type, expires_in, expires, scope, subject, refresh_token, id_token]
     */
    public function request(array $params, array $options = [])
    {
        return self::curl("POST", "", array_filter($params, fn($value) => $value !== null), @$options["headers"] ?? []);
    }

    /**
     * @param string $clientId ID of client
     * @param string $clientSecret Secret of client
     * @param null|string $scope Space delimited scopes, all granted scopes when omitted
     * @param array $options
     */
    public function clientCredentials($clientId, $clientSecret, $scope = null, array $options = [])
    {
        return $this->request([
            "grant_type" => self::GRANT_TYPE_CLIENT_CREDENTIALS,
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "scope" => $scope,
        ], $options);
    }

    /**
     * Returns [flow, mfa_token] instead of a token when MFA is enabled, complete the login with mfa
     * @param string $clientId ID of client
     * @param string $username User email
     * @param string $password User password
     * @param null|string $scope Space delimited scopes, all granted scopes when omitted
     * @param array $options
     */
    public function password($clientId, $username, $password, $scope = null, array $options = [])
    {
        return $this->request([
            "grant_type" => self::GRANT_TYPE_PASSWORD,
            "client_id" => $clientId,
            "username" => $username,
            "password" => $password,
            "scope" => $scope,
        ], $options);
    }

    /**
     * @param string $clientId ID of client
     * @param string $code Authorization code received on the redirect uri
     * @param string $codeVerifier PKCE code verifier of the authorization request
     * @param string $redirectURI Redirect uri of the authorization request
     * @param array $params client_secret
     * @param array $options
     */
    public function authorizationCode($clientId, $code, $codeVerifier, $redirectURI, array $params = [], array $options = [])
    {
        return $this->request(array_merge([
            "grant_type" => self::GRANT_TYPE_AUTHORIZATION_CODE,
            "client_id" => $clientId,
            "code" => $code,
            "code_verifier" => $codeVerifier,
            "redirect_uri" => $redirectURI,
        ], $params), $options);
    }

    /**
     * Returns a new refresh token that replaces the used one
     * @param string $clientId ID of client
     * @param string $refreshToken Refresh token
     * @param null|string $scope Narrower scope, space delimited
     * @param array $params client_secret
     * @param array $options
     */
    public function refreshToken($clientId, $refreshToken, $scope = null, array $params = [], array $options = [])
    {
        return $this->request(array_merge([
            "grant_type" => self::GRANT_TYPE_REFRESH_TOKEN,
            "client_id" => $clientId,
            "refresh_token" => $refreshToken,
            "scope" => $scope,
        ], $params), $options);
    }

    /**
     * Completes a password login that returned [flow => mfa, mfa_token]
     * @param string $clientId ID of client
     * @param string $mfaToken mfa_token of the password login
     * @param string $otp One-time code received by the user
     * @param array $options
     */
    public function mfa($clientId, $mfaToken, $otp, array $options = [])
    {
        return $this->request([
            "grant_type" => self::GRANT_TYPE_MFA_OTP,
            "client_id" => $clientId,
            "mfa_token" => $mfaToken,
            "otp" => $otp,
        ], $options);
    }

    /**
     * Exchanges the id_token of an identity provider enabled for the tenant (e.g. Sign in with Google) for a token
     * @param string $clientId ID of client
     * @param string $assertion id_token of the identity provider
     * @param null|string $scope Space delimited scopes, all granted scopes when omitted
     * @param array $options
     */
    public function jwtBearer($clientId, $assertion, $scope = null, array $options = [])
    {
        return $this->request([
            "grant_type" => self::GRANT_TYPE_JWT_BEARER,
            "client_id" => $clientId,
            "assertion" => $assertion,
            "scope" => $scope,
        ], $options);
    }
}
