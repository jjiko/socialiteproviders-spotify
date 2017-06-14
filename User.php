<?php

namespace SocialiteProviders\Spotify;

use SocialiteProviders\Manager\OAuth2\User as BaseUser;

class User extends BaseUser
{
  public $refreshToken;
  public $expiresIn;

  public function setRefreshToken($refreshToken)
  {
    $this->refreshToken = $refreshToken;

    return $this;
  }

  public function setExpiresIn($expiresIn)
  {
    $this->expiresIn = $expiresIn;

    return $this;
  }
}
