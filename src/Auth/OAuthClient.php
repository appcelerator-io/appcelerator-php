<?php
declare(strict_types=1);

namespace AppCelerator\Auth;

use AppCelerator\Exceptions\AppCeleratorException;
use AppCelerator\Exceptions\AppCeleratorHttpException;
use AppCelerator\Utils\HttpClient;

/**
 * OAuthClient
 *  The token endpoint of the OAuth2 service (e.g. https://myapp.oauth2.eu.appcelerator.io/token), authenticated with the
 *  client credentials. A failed request throws AppCeleratorHttpException, getOAuthError() tells why (e.g. invalid_scope).
 */
class OAuthClient implements TokenEndpoint
{
    private const TOKEN_EXCHANGE = "urn:ietf:params:oauth:grant-type:token-exchange";
    private const ACCESS_TOKEN = "urn:ietf:params:oauth:token-type:access_token";

    public function __construct(private string $tokenUrl, private Credentials $credentials, private int $timeout = 10)
    {
        if($tokenUrl === "")
            throw new AppCeleratorException("The token URL of the OAuth2 service is missing");
    }

    public function clientCredentials(string $scope) : array
    {
        return $this->request(["grant_type" => "client_credentials", "scope" => $scope]);
    }

    public function tokenExchange(#[\SensitiveParameter] string $subjectToken, string $scope, null|string $tenant = null) : array
    {
        return $this->request(array_filter([
            "grant_type" => self::TOKEN_EXCHANGE,
            "subject_token" => $subjectToken,
            "subject_token_type" => self::ACCESS_TOKEN,
            "scope" => $scope,
            "tenant" => $tenant,
        ], fn($value) => $value !== null));
    }

    /**
     * request
     */
    private function request(#[\SensitiveParameter] array $params) : array
    {
        $params += ["client_id" => $this->credentials->clientId, "client_secret" => $this->credentials->secret()];

        $response = HttpClient::send("POST", $this->tokenUrl, http_build_query($params, "", "&", PHP_QUERY_RFC3986), [
            "Content-Type" => "application/x-www-form-urlencoded",
            "Accept" => "application/json",
        ], $this->timeout);

        if(!$response->isSuccessful())
            throw new AppCeleratorHttpException($response);

        $data = $response->getParameters();
        $token = $data["access_token"] ?? null;

        if(!is_string($token) || $token === "")
            throw new AppCeleratorHttpException($response);

        return ["token" => $token, "expires" => self::expires($data, $token)];
    }

    /**
     * expires
     *  The expiry as unix time: expires (this OAuth2 service), expires_in (RFC 6749) or the exp of the JWT
     */
    private static function expires(array $data, string $token) : int
    {
        if(is_numeric($data["expires"] ?? null))
            return (int) $data["expires"];

        if(is_numeric($data["expires_in"] ?? null))
            return time() + (int) $data["expires_in"];

        $parts = explode(".", $token);
        $payload = count($parts) === 3 ? json_decode((string) base64_decode(strtr($parts[1], "-_", "+/")), true) : null;

        return is_int($payload["exp"] ?? null) ? $payload["exp"] : time() + 300;
    }
}
