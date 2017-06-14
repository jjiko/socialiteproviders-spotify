<?php

namespace SocialiteProviders\Spotify;

use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use GuzzleHttp\ClientInterface;

class Provider extends AbstractProvider implements ProviderInterface
{
  /**
   * Unique Provider Identifier.
   */
  const IDENTIFIER = 'SPOTIFY';

  /**
   * The separating character for the requested scopes.
   *
   * @var string
   */
  protected $scopeSeparator = ' ';

  /**
   * {@inheritdoc}
   */
  protected function getAuthUrl($state)
  {
    return $this->buildAuthUrlFromBase(
      'https://accounts.spotify.com/authorize', $state
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getTokenUrl()
  {
    return 'https://accounts.spotify.com/api/token';
  }

  /**
   * {@inheritdoc}
   */
  protected function getUserByToken($token)
  {
    $response = $this->getHttpClient()->get(
      'https://api.spotify.com/v1/me', [
      'headers' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
      ],
    ]);

    return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * {@inheritdoc}
   */
  protected function mapUserToObject(array $user)
  {
    return (new User())->setRaw($user)->map([
      'id' => $user['id'],
      'nickname' => null,
      'name' => $user['display_name'],
      'email' => isset($user['email']) ? $user['email'] : null,
      'avatar' => array_get($user, 'images.0.url'),
      'profileUrl' => isset($user['href']) ? $user['href'] : null,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTokenFields($code)
  {
    return array_merge(parent::getTokenFields($code), [
      'grant_type' => 'authorization_code',
    ]);
  }

  /**
   * Get the access token for the given code.
   *
   * @param  string $code
   * @return string
   */
  public function getAccessToken($code)
  {
    $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
    $response = $this->getHttpClient()->post($this->getTokenUrl(), [
      'headers' => ['Accept' => 'application/json'],
      $postKey => $this->getTokenFields($code),
    ]);
    return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * @return \SocialiteProviders\Manager\OAuth2\User
   */
  public function user()
  {
    if ($this->hasInvalidState()) {
      throw new InvalidStateException();
    }

    $response = $this->getAccessToken($this->getCode());
    $user = $this->mapUserToObject($this->getUserByToken(
      $token = $this->parseAccessToken($response)
    ));

    $this->credentialsResponseBody = $response;

    if ($user instanceof User) {
      $user->setAccessTokenResponseBody($this->credentialsResponseBody);
    }

    return $user->setToken($token)
      ->setRefreshToken($this->parseRefreshToken($response))
      ->setExpiresIn($this->parseExpiresIn($response));
  }
}
