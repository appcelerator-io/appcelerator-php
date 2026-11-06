<?php
/**
 * A stub of the platform for ClientRequestTest (php -S, router script):
 *      POST /oauth2/token          - hands out tokens t1, t2, ... and records the request
 *      /{service}/reject-first/... - 401 for token t1, otherwise like any other path
 *      /{service}/...              - echoes the request as JSON
 * STUB_STATE is the directory of its state.
 */
$state = getenv("STUB_STATE");
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$headers = array_change_key_case(function_exists("getallheaders") ? getallheaders() : [], CASE_LOWER);
$body = file_get_contents("php://input");

header("Content-Type: application/json");

if($path === "/oauth2/token")
{
    parse_str($body, $params);

    $count = (int) @file_get_contents("$state/count") + 1;
    file_put_contents("$state/count", (string) $count);
    file_put_contents("$state/token-requests", json_encode($params) . "\n", FILE_APPEND);

    if(($params["client_secret"] ?? null) !== "secret")
    {
        http_response_code(401);
        echo json_encode(["error" => "invalid_client", "error_description" => "Client authentication failed", "error_code" => "credentialsInvalid"]);
        return;
    }

    echo json_encode(["access_token" => "t$count", "expires" => time() + 3600, "token_type" => "Bearer"]);
    return;
}

if(str_contains($path, "/reject-first/") && ($headers["authorization"] ?? null) === "Bearer t1")
{
    http_response_code(401);
    echo json_encode(["statusCode" => 401, "error" => "tokenKeyNotFound", "errorDescription" => "You need a valid token"]);
    return;
}

if(str_contains($path, "/not-found/"))
    http_response_code(404);

echo json_encode([
    "data" => [
        "method" => $_SERVER["REQUEST_METHOD"],
        "path" => $path,
        "query" => $_GET,
        "authorization" => $headers["authorization"] ?? null,
        "identifier" => $headers["identifier"] ?? null,
        "contentType" => $headers["content-type"] ?? null,
        "custom" => $headers["x-custom"] ?? null,
        "body" => $body,
    ],
]);
