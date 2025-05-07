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

namespace Google\Service\Aiplatform;

class GoogleCloudAiplatformV1RagFile extends \Google\Model
{
  /**
   * @var string
   */
  public $createTime;
  /**
   * @var string
   */
  public $description;
  protected $directUploadSourceType = GoogleCloudAiplatformV1DirectUploadSource::class;
  protected $directUploadSourceDataType = '';
  /**
   * @var string
   */
  public $displayName;
  protected $fileStatusType = GoogleCloudAiplatformV1FileStatus::class;
  protected $fileStatusDataType = '';
  protected $gcsSourceType = GoogleCloudAiplatformV1GcsSource::class;
  protected $gcsSourceDataType = '';
  protected $googleDriveSourceType = GoogleCloudAiplatformV1GoogleDriveSource::class;
  protected $googleDriveSourceDataType = '';
  protected $jiraSourceType = GoogleCloudAiplatformV1JiraSource::class;
  protected $jiraSourceDataType = '';
  /**
   * @var string
   */
  public $name;
  protected $sharePointSourcesType = GoogleCloudAiplatformV1SharePointSources::class;
  protected $sharePointSourcesDataType = '';
  protected $slackSourceType = GoogleCloudAiplatformV1SlackSource::class;
  protected $slackSourceDataType = '';
  /**
   * @var string
   */
  public $updateTime;

  /**
   * @param string
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
   * @param string
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }
  /**
   * @param GoogleCloudAiplatformV1DirectUploadSource
   */
  public function setDirectUploadSource(GoogleCloudAiplatformV1DirectUploadSource $directUploadSource)
  {
    $this->directUploadSource = $directUploadSource;
  }
  /**
   * @return GoogleCloudAiplatformV1DirectUploadSource
   */
  public function getDirectUploadSource()
  {
    return $this->directUploadSource;
  }
  /**
   * @param string
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
   * @param GoogleCloudAiplatformV1FileStatus
   */
  public function setFileStatus(GoogleCloudAiplatformV1FileStatus $fileStatus)
  {
    $this->fileStatus = $fileStatus;
  }
  /**
   * @return GoogleCloudAiplatformV1FileStatus
   */
  public function getFileStatus()
  {
    return $this->fileStatus;
  }
  /**
   * @param GoogleCloudAiplatformV1GcsSource
   */
  public function setGcsSource(GoogleCloudAiplatformV1GcsSource $gcsSource)
  {
    $this->gcsSource = $gcsSource;
  }
  /**
   * @return GoogleCloudAiplatformV1GcsSource
   */
  public function getGcsSource()
  {
    return $this->gcsSource;
  }
  /**
   * @param GoogleCloudAiplatformV1GoogleDriveSource
   */
  public function setGoogleDriveSource(GoogleCloudAiplatformV1GoogleDriveSource $googleDriveSource)
  {
    $this->googleDriveSource = $googleDriveSource;
  }
  /**
   * @return GoogleCloudAiplatformV1GoogleDriveSource
   */
  public function getGoogleDriveSource()
  {
    return $this->googleDriveSource;
  }
  /**
   * @param GoogleCloudAiplatformV1JiraSource
   */
  public function setJiraSource(GoogleCloudAiplatformV1JiraSource $jiraSource)
  {
    $this->jiraSource = $jiraSource;
  }
  /**
   * @return GoogleCloudAiplatformV1JiraSource
   */
  public function getJiraSource()
  {
    return $this->jiraSource;
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
   * @param GoogleCloudAiplatformV1SharePointSources
   */
  public function setSharePointSources(GoogleCloudAiplatformV1SharePointSources $sharePointSources)
  {
    $this->sharePointSources = $sharePointSources;
  }
  /**
   * @return GoogleCloudAiplatformV1SharePointSources
   */
  public function getSharePointSources()
  {
    return $this->sharePointSources;
  }
  /**
   * @param GoogleCloudAiplatformV1SlackSource
   */
  public function setSlackSource(GoogleCloudAiplatformV1SlackSource $slackSource)
  {
    $this->slackSource = $slackSource;
  }
  /**
   * @return GoogleCloudAiplatformV1SlackSource
   */
  public function getSlackSource()
  {
    return $this->slackSource;
  }
  /**
   * @param string
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
class_alias(GoogleCloudAiplatformV1RagFile::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1RagFile');
