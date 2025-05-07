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

namespace Google\Service\CloudHealthcare;

class ApplyAdminConsentsErrorDetail extends \Google\Collection
{
  protected $collection_key = 'consentErrors';
  protected $consentErrorsType = ConsentErrors::class;
  protected $consentErrorsDataType = 'array';
  /**
   * @var string
   */
  public $existingOperationId;

  /**
   * @param ConsentErrors[]
   */
  public function setConsentErrors($consentErrors)
  {
    $this->consentErrors = $consentErrors;
  }
  /**
   * @return ConsentErrors[]
   */
  public function getConsentErrors()
  {
    return $this->consentErrors;
  }
  /**
   * @param string
   */
  public function setExistingOperationId($existingOperationId)
  {
    $this->existingOperationId = $existingOperationId;
  }
  /**
   * @return string
   */
  public function getExistingOperationId()
  {
    return $this->existingOperationId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ApplyAdminConsentsErrorDetail::class, 'Google_Service_CloudHealthcare_ApplyAdminConsentsErrorDetail');
