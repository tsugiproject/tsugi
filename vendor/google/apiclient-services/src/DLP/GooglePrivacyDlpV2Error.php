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

namespace Google\Service\DLP;

class GooglePrivacyDlpV2Error extends \Google\Collection
{
  protected $collection_key = 'timestamps';
  protected $detailsType = GoogleRpcStatus::class;
  protected $detailsDataType = '';
  /**
   * @var string
   */
  public $extraInfo;
  /**
   * @var string[]
   */
  public $timestamps;

  /**
   * @param GoogleRpcStatus
   */
  public function setDetails(GoogleRpcStatus $details)
  {
    $this->details = $details;
  }
  /**
   * @return GoogleRpcStatus
   */
  public function getDetails()
  {
    return $this->details;
  }
  /**
   * @param string
   */
  public function setExtraInfo($extraInfo)
  {
    $this->extraInfo = $extraInfo;
  }
  /**
   * @return string
   */
  public function getExtraInfo()
  {
    return $this->extraInfo;
  }
  /**
   * @param string[]
   */
  public function setTimestamps($timestamps)
  {
    $this->timestamps = $timestamps;
  }
  /**
   * @return string[]
   */
  public function getTimestamps()
  {
    return $this->timestamps;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GooglePrivacyDlpV2Error::class, 'Google_Service_DLP_GooglePrivacyDlpV2Error');
