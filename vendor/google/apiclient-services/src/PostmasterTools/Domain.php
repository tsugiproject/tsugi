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

namespace Google\Service\PostmasterTools;

class Domain extends \Google\Model
{
  /**
   * Unspecified permission.
   */
  public const PERMISSION_PERMISSION_UNSPECIFIED = 'PERMISSION_UNSPECIFIED';
  /**
   * User has read access to the domain.
   */
  public const PERMISSION_READER = 'READER';
  /**
   * User has owner access to the domain.
   */
  public const PERMISSION_OWNER = 'OWNER';
  /**
   * User has no access to the domain.
   */
  public const PERMISSION_NONE = 'NONE';
  /**
   * Unspecified.
   */
  public const VERIFICATION_STATE_VERIFICATION_STATE_UNSPECIFIED = 'VERIFICATION_STATE_UNSPECIFIED';
  /**
   * The domain is unverified.
   */
  public const VERIFICATION_STATE_UNVERIFIED = 'UNVERIFIED';
  /**
   * The domain is verified.
   */
  public const VERIFICATION_STATE_VERIFIED = 'VERIFIED';
  /**
   * Output only. Immutable. The timestamp at which the domain was added to the
   * user's account.
   *
   * @var string
   */
  public $createTime;
  /**
   * The timestamp at which the domain was last verified by the user.
   *
   * @var string
   */
  public $lastVerifyTime;
  /**
   * Identifier. The resource name of the domain. Format:
   * `domains/{domain_name}`, where domain_name is the fully qualified domain
   * name (i.e., mymail.mydomain.com).
   *
   * @var string
   */
  public $name;
  /**
   * Output only. User's permission of this domain.
   *
   * @var string
   */
  public $permission;
  /**
   * Output only. Information about a user's verification history and properties
   * for the domain.
   *
   * @var string
   */
  public $verificationState;

  /**
   * Output only. Immutable. The timestamp at which the domain was added to the
   * user's account.
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
   * The timestamp at which the domain was last verified by the user.
   *
   * @param string $lastVerifyTime
   */
  public function setLastVerifyTime($lastVerifyTime)
  {
    $this->lastVerifyTime = $lastVerifyTime;
  }
  /**
   * @return string
   */
  public function getLastVerifyTime()
  {
    return $this->lastVerifyTime;
  }
  /**
   * Identifier. The resource name of the domain. Format:
   * `domains/{domain_name}`, where domain_name is the fully qualified domain
   * name (i.e., mymail.mydomain.com).
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
   * Output only. User's permission of this domain.
   *
   * Accepted values: PERMISSION_UNSPECIFIED, READER, OWNER, NONE
   *
   * @param self::PERMISSION_* $permission
   */
  public function setPermission($permission)
  {
    $this->permission = $permission;
  }
  /**
   * @return self::PERMISSION_*
   */
  public function getPermission()
  {
    return $this->permission;
  }
  /**
   * Output only. Information about a user's verification history and properties
   * for the domain.
   *
   * Accepted values: VERIFICATION_STATE_UNSPECIFIED, UNVERIFIED, VERIFIED
   *
   * @param self::VERIFICATION_STATE_* $verificationState
   */
  public function setVerificationState($verificationState)
  {
    $this->verificationState = $verificationState;
  }
  /**
   * @return self::VERIFICATION_STATE_*
   */
  public function getVerificationState()
  {
    return $this->verificationState;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Domain::class, 'Google_Service_PostmasterTools_Domain');
