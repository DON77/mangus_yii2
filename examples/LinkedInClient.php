<?php

namespace app\components\liknedin;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class LinkedInClient
{
  /**
   * @var Client
   */
  private $httpClient;
  private $clientId;
  private $clientSecret;
  private $accessToken;

  /**
   * LinkedInClient constructor.
   * @param $clientId
   * @param $appSecret
   * @param null $accessToken
   */
  public function __construct($clientId, $appSecret, $accessToken = null)
  {
    if ($accessToken) {
      $this->setAccessToken($accessToken);
    }
    $this->clientId = $clientId;
    $this->clientSecret = $appSecret;
    $this->httpClient = new Client([
      'base_uri' => 'https://api.linkedin.com/v1/',
    ]);
  }

  /**
   * @param ResponseInterface $response
   * @return mixed
   */
  private function parseResponse(ResponseInterface $response)
  {
    return \GuzzleHttp\json_decode($response->getBody(), true);
  }

  /**
   * @param array $scope
   * @param $redirectUrl
   * @return array
   */
  public function getLoginUrl(array $scope = [], $redirectUrl)
  {
    $csrf = uniqid();
    $url = sprintf('https://www.linkedin.com/oauth/v2/authorization?%s', http_build_query([
      'response_type' => 'code',
      'client_id' => $this->clientId,
      'redirect_uri' => $redirectUrl,
      'state' => $csrf,
      'scope' => implode(',', $scope)
    ]));
    return [
      'url' => $url,
      'state' => $csrf
    ];
  }

  /**
   * @param $code
   * @param $redirectUrl
   * @return mixed
   */
  public function getAccessTokenByCode($code, $redirectUrl)
  {
    $response = $this->parseResponse($this->httpClient->post('https://www.linkedin.com/oauth/v2/accessToken', ['form_params' => [
      'grant_type' => 'authorization_code',
      'code' => $code,
      'redirect_uri' => $redirectUrl,
      'client_id' => $this->clientId,
      'client_secret' => $this->clientSecret,
    ]]));
    return $response;
  }

  /**
   * @param $accessToken
   */
  public function setAccessToken($accessToken)
  {
    $this->accessToken = $accessToken;
  }

  /**
   * @return mixed
   */
  public function getAccessToken()
  {
    return $this->accessToken;
  }

  /**
   * @param $method
   * @param $endpoint
   * @param array $params
   * @return mixed
   */
  public function api($method, $endpoint, $params = [])
  {
    if (strtolower($method) == 'get') {
      $query = $params;
      $json = [];
    } else {
      $json = $params;
    }
    $query['format'] = 'json';
    $query['oauth2_access_token'] = $this->accessToken;
    $response = $this->parseResponse($this->httpClient->request($method, $endpoint, ['query' => $query, 'json' => $json]));
    return $response;
  }
}