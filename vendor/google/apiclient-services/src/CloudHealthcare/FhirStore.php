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

class FhirStore extends \Google\Collection
{
  protected $collection_key = 'streamConfigs';
  protected $bulkExportGcsDestinationType = BulkExportGcsDestination::class;
  protected $bulkExportGcsDestinationDataType = '';
  /**
   * @var string
   */
  public $complexDataTypeReferenceParsing;
  protected $consentConfigType = ConsentConfig::class;
  protected $consentConfigDataType = '';
  /**
   * @var bool
   */
  public $defaultSearchHandlingStrict;
  /**
   * @var bool
   */
  public $disableReferentialIntegrity;
  /**
   * @var bool
   */
  public $disableResourceVersioning;
  /**
   * @var bool
   */
  public $enableUpdateCreate;
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $name;
  protected $notificationConfigType = NotificationConfig::class;
  protected $notificationConfigDataType = '';
  protected $notificationConfigsType = FhirNotificationConfig::class;
  protected $notificationConfigsDataType = 'array';
  protected $streamConfigsType = StreamConfig::class;
  protected $streamConfigsDataType = 'array';
  protected $validationConfigType = ValidationConfig::class;
  protected $validationConfigDataType = '';
  /**
   * @var string
   */
  public $version;

  /**
   * @param BulkExportGcsDestination
   */
  public function setBulkExportGcsDestination(BulkExportGcsDestination $bulkExportGcsDestination)
  {
    $this->bulkExportGcsDestination = $bulkExportGcsDestination;
  }
  /**
   * @return BulkExportGcsDestination
   */
  public function getBulkExportGcsDestination()
  {
    return $this->bulkExportGcsDestination;
  }
  /**
   * @param string
   */
  public function setComplexDataTypeReferenceParsing($complexDataTypeReferenceParsing)
  {
    $this->complexDataTypeReferenceParsing = $complexDataTypeReferenceParsing;
  }
  /**
   * @return string
   */
  public function getComplexDataTypeReferenceParsing()
  {
    return $this->complexDataTypeReferenceParsing;
  }
  /**
   * @param ConsentConfig
   */
  public function setConsentConfig(ConsentConfig $consentConfig)
  {
    $this->consentConfig = $consentConfig;
  }
  /**
   * @return ConsentConfig
   */
  public function getConsentConfig()
  {
    return $this->consentConfig;
  }
  /**
   * @param bool
   */
  public function setDefaultSearchHandlingStrict($defaultSearchHandlingStrict)
  {
    $this->defaultSearchHandlingStrict = $defaultSearchHandlingStrict;
  }
  /**
   * @return bool
   */
  public function getDefaultSearchHandlingStrict()
  {
    return $this->defaultSearchHandlingStrict;
  }
  /**
   * @param bool
   */
  public function setDisableReferentialIntegrity($disableReferentialIntegrity)
  {
    $this->disableReferentialIntegrity = $disableReferentialIntegrity;
  }
  /**
   * @return bool
   */
  public function getDisableReferentialIntegrity()
  {
    return $this->disableReferentialIntegrity;
  }
  /**
   * @param bool
   */
  public function setDisableResourceVersioning($disableResourceVersioning)
  {
    $this->disableResourceVersioning = $disableResourceVersioning;
  }
  /**
   * @return bool
   */
  public function getDisableResourceVersioning()
  {
    return $this->disableResourceVersioning;
  }
  /**
   * @param bool
   */
  public function setEnableUpdateCreate($enableUpdateCreate)
  {
    $this->enableUpdateCreate = $enableUpdateCreate;
  }
  /**
   * @return bool
   */
  public function getEnableUpdateCreate()
  {
    return $this->enableUpdateCreate;
  }
  /**
   * @param string[]
   */
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  /**
   * @return string[]
   */
  public function getLabels()
  {
    return $this->labels;
  }
  /**
   * @param string
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
   * @param NotificationConfig
   */
  public function setNotificationConfig(NotificationConfig $notificationConfig)
  {
    $this->notificationConfig = $notificationConfig;
  }
  /**
   * @return NotificationConfig
   */
  public function getNotificationConfig()
  {
    return $this->notificationConfig;
  }
  /**
   * @param FhirNotificationConfig[]
   */
  public function setNotificationConfigs($notificationConfigs)
  {
    $this->notificationConfigs = $notificationConfigs;
  }
  /**
   * @return FhirNotificationConfig[]
   */
  public function getNotificationConfigs()
  {
    return $this->notificationConfigs;
  }
  /**
   * @param StreamConfig[]
   */
  public function setStreamConfigs($streamConfigs)
  {
    $this->streamConfigs = $streamConfigs;
  }
  /**
   * @return StreamConfig[]
   */
  public function getStreamConfigs()
  {
    return $this->streamConfigs;
  }
  /**
   * @param ValidationConfig
   */
  public function setValidationConfig(ValidationConfig $validationConfig)
  {
    $this->validationConfig = $validationConfig;
  }
  /**
   * @return ValidationConfig
   */
  public function getValidationConfig()
  {
    return $this->validationConfig;
  }
  /**
   * @param string
   */
  public function setVersion($version)
  {
    $this->version = $version;
  }
  /**
   * @return string
   */
  public function getVersion()
  {
    return $this->version;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(FhirStore::class, 'Google_Service_CloudHealthcare_FhirStore');
