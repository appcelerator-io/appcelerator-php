<?php
declare(strict_types=1);

namespace AppCelerator\Exceptions;

use AppCelerator\Utils\Response;
use Exception;

/**
 * AppCeleratorHttpException
 *  A failed API call. The API answers errors in one of two formats:
 *      API endpoints   - {statusCode, error, errorDescription}, error is the error code e.g. tokenInvalid
 *      /token (OAuth2) - {error, error_description, error_code}, error is the OAuth2 error e.g. invalid_grant, error_code the error code
 *  getErrorCode returns the error code for both, e.g. credentialsInvalid or scopeInsufficient
 */
class AppCeleratorHttpException extends Exception
{
    public static $showUrl = false;

    private array $errorData;

    public function __construct(private Response $response)
    {
        $this->errorData = $response->getParameters();

        $message = (string) $response->getStatusCode();

        if($this->getErrorCode() !== null)
            $message .= " - " . $this->getErrorCode();

        if($this->getErrorDescription() !== null)
            $message .= " - " . $this->getErrorDescription();

        if(!$response->isJson() && strlen($response->getContent()) > 0)
            $message .= " - Response is not JSON: " . substr($response->getContent(), 0, 200);

        if(self::$showUrl && $response->hasUrl())
            $message .= " - (url: " . $response->getUrl() . ")";

        parent::__construct($message, $response->getStatusCode());
    }

    public function getResponse() : Response
    {
        return $this->response;
    }

    public function getStatusCode() : int
    {
        return $this->response->getStatusCode();
    }

    /**
     * The error code of the API e.g. tokenInvalid, credentialsInvalid, scopeInsufficient
     */
    public function getErrorCode() : null|string
    {
        $errorCode = $this->errorData["error_code"] ?? $this->errorData["error"] ?? null;

        return is_string($errorCode) ? $errorCode : null;
    }

    /**
     * The OAuth2 error (RFC 6749) of a /token request e.g. invalid_grant, null for other endpoints
     */
    public function getOAuthError() : null|string
    {
        return isset($this->errorData["error_code"]) && is_string($this->errorData["error"] ?? null) ? $this->errorData["error"] : null;
    }

    public function getErrorDescription() : null|string
    {
        $errorDescription = $this->errorData["errorDescription"] ?? $this->errorData["error_description"] ?? null;

        return is_string($errorDescription) ? $errorDescription : null;
    }

    /**
     * The complete decoded error response, including additional error details
     */
    public function getErrorData() : array
    {
        return $this->errorData;
    }
}
