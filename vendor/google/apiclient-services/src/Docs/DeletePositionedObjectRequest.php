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

namespace Google\Service\Docs;

class DeletePositionedObjectRequest extends \Google\Model
{
  /**
   * @var string
   */
  public $objectId;
  /**
   * @var string
   */
  public $tabId;

  /**
   * @param string
   */
  public function setObjectId($objectId)
  {
    $this->objectId = $objectId;
  }
  /**
   * @return string
   */
  public function getObjectId()
  {
    return $this->objectId;
  }
  /**
   * @param string
   */
  public function setTabId($tabId)
  {
    $this->tabId = $tabId;
  }
  /**
   * @return string
   */
  public function getTabId()
  {
    return $this->tabId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DeletePositionedObjectRequest::class, 'Google_Service_Docs_DeletePositionedObjectRequest');
