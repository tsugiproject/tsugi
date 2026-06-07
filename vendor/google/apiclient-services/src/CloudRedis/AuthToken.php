<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\CloudRedis;

class AuthToken extends \Google\Model
{
  /**
   * Not set.
   */
  public const STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * The auth token is active.
   */
  public const STATE_ACTIVE = 'ACTIVE';
  /**
   * The auth token is being created.
   */
  public const STATE_CREATING = 'CREATING';
  /**
   * The auth token is being deleted.
   */
  public const STATE_DELETING = 'DELETING';
  /**
   * Output only. Create time of the auth token.
   *
   * @var string
   */
  public $createTime;
  /**
   * Identifier. Name of the auth token. Format: projects/{project}/locations/{l
   * ocation}/clusters/{cluster}/tokenAuthUsers/{token_auth_user}/authTokens/{au
   * th_token}
   *
   * @var string
   */
  public $name;
  /**
   * Output only. State of the auth token.
   *
   * @var string
   */
  public $state;
  /**
   * Output only. The service generated authentication token used to connect to
   * the Redis cluster.
   *
   * @var string
   */
  public $token;

  /**
   * Output only. Create time of the auth token.
   *
   * @param string $createTime
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * Identifier. Name of the auth token. Format: projects/{project}/locations/{l
   * ocation}/clusters/{cluster}/tokenAuthUsers/{token_auth_user}/authTokens/{au
   * th_token}
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * Output only. State of the auth token.
   *
   * Accepted values: STATE_UNSPECIFIED, ACTIVE, CREATING, DELETING
   *
   * @param self::STATE_* $state
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return self::STATE_*
   */
  public function getState()
  {
    return $this->state;
  }
  /**
   * Output only. The service generated authentication token used to connect to
   * the Redis cluster.
   *
   * @param string $token
   */
  public function setToken($token)
  {
    $this->token = $token;
  }
  /**
   * @return string
   */
  public function getToken()
  {
    return $this->token;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AuthToken::class, 'Google_Service_CloudRedis_AuthToken');
