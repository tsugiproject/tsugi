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

namespace Google\Service\Dataform;

class TeamFolder extends \Google\Model
{
  /**
   * Output only. The timestamp of when the TeamFolder was created.
   *
   * @var string
   */
  public $createTime;
  /**
   * Output only. The IAM principal identifier of the creator of the TeamFolder.
   *
   * @var string
   */
  public $creatorIamPrincipal;
  /**
   * Required. The TeamFolder's user-friendly name.
   *
   * @var string
   */
  public $displayName;
  /**
   * Output only. All the metadata information that is used internally to serve
   * the resource. For example: timestamps, flags, status fields, etc. The
   * format of this field is a JSON string.
   *
   * @var string
   */
  public $internalMetadata;
  /**
   * Identifier. The TeamFolder's name.
   *
   * @var string
   */
  public $name;
  /**
   * Output only. The timestamp of when the TeamFolder was last updated.
   *
   * @var string
   */
  public $updateTime;

  /**
   * Output only. The timestamp of when the TeamFolder was created.
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
   * Output only. The IAM principal identifier of the creator of the TeamFolder.
   *
   * @param string $creatorIamPrincipal
   */
  public function setCreatorIamPrincipal($creatorIamPrincipal)
  {
    $this->creatorIamPrincipal = $creatorIamPrincipal;
  }
  /**
   * @return string
   */
  public function getCreatorIamPrincipal()
  {
    return $this->creatorIamPrincipal;
  }
  /**
   * Required. The TeamFolder's user-friendly name.
   *
   * @param string $displayName
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * Output only. All the metadata information that is used internally to serve
   * the resource. For example: timestamps, flags, status fields, etc. The
   * format of this field is a JSON string.
   *
   * @param string $internalMetadata
   */
  public function setInternalMetadata($internalMetadata)
  {
    $this->internalMetadata = $internalMetadata;
  }
  /**
   * @return string
   */
  public function getInternalMetadata()
  {
    return $this->internalMetadata;
  }
  /**
   * Identifier. The TeamFolder's name.
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
   * Output only. The timestamp of when the TeamFolder was last updated.
   *
   * @param string $updateTime
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(TeamFolder::class, 'Google_Service_Dataform_TeamFolder');
