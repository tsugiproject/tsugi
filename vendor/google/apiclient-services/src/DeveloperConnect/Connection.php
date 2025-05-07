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

namespace Google\Service\DeveloperConnect;

class Connection extends \Google\Model
{
  /**
   * @var string[]
   */
  public $annotations;
  protected $bitbucketCloudConfigType = BitbucketCloudConfig::class;
  protected $bitbucketCloudConfigDataType = '';
  protected $bitbucketDataCenterConfigType = BitbucketDataCenterConfig::class;
  protected $bitbucketDataCenterConfigDataType = '';
  /**
   * @var string
   */
  public $createTime;
  protected $cryptoKeyConfigType = CryptoKeyConfig::class;
  protected $cryptoKeyConfigDataType = '';
  /**
   * @var string
   */
  public $deleteTime;
  /**
   * @var bool
   */
  public $disabled;
  /**
   * @var string
   */
  public $etag;
  protected $gitProxyConfigType = GitProxyConfig::class;
  protected $gitProxyConfigDataType = '';
  protected $githubConfigType = GitHubConfig::class;
  protected $githubConfigDataType = '';
  protected $githubEnterpriseConfigType = GitHubEnterpriseConfig::class;
  protected $githubEnterpriseConfigDataType = '';
  protected $gitlabConfigType = GitLabConfig::class;
  protected $gitlabConfigDataType = '';
  protected $gitlabEnterpriseConfigType = GitLabEnterpriseConfig::class;
  protected $gitlabEnterpriseConfigDataType = '';
  protected $installationStateType = InstallationState::class;
  protected $installationStateDataType = '';
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $name;
  /**
   * @var bool
   */
  public $reconciling;
  /**
   * @var string
   */
  public $uid;
  /**
   * @var string
   */
  public $updateTime;

  /**
   * @param string[]
   */
  public function setAnnotations($annotations)
  {
    $this->annotations = $annotations;
  }
  /**
   * @return string[]
   */
  public function getAnnotations()
  {
    return $this->annotations;
  }
  /**
   * @param BitbucketCloudConfig
   */
  public function setBitbucketCloudConfig(BitbucketCloudConfig $bitbucketCloudConfig)
  {
    $this->bitbucketCloudConfig = $bitbucketCloudConfig;
  }
  /**
   * @return BitbucketCloudConfig
   */
  public function getBitbucketCloudConfig()
  {
    return $this->bitbucketCloudConfig;
  }
  /**
   * @param BitbucketDataCenterConfig
   */
  public function setBitbucketDataCenterConfig(BitbucketDataCenterConfig $bitbucketDataCenterConfig)
  {
    $this->bitbucketDataCenterConfig = $bitbucketDataCenterConfig;
  }
  /**
   * @return BitbucketDataCenterConfig
   */
  public function getBitbucketDataCenterConfig()
  {
    return $this->bitbucketDataCenterConfig;
  }
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
   * @param CryptoKeyConfig
   */
  public function setCryptoKeyConfig(CryptoKeyConfig $cryptoKeyConfig)
  {
    $this->cryptoKeyConfig = $cryptoKeyConfig;
  }
  /**
   * @return CryptoKeyConfig
   */
  public function getCryptoKeyConfig()
  {
    return $this->cryptoKeyConfig;
  }
  /**
   * @param string
   */
  public function setDeleteTime($deleteTime)
  {
    $this->deleteTime = $deleteTime;
  }
  /**
   * @return string
   */
  public function getDeleteTime()
  {
    return $this->deleteTime;
  }
  /**
   * @param bool
   */
  public function setDisabled($disabled)
  {
    $this->disabled = $disabled;
  }
  /**
   * @return bool
   */
  public function getDisabled()
  {
    return $this->disabled;
  }
  /**
   * @param string
   */
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  /**
   * @return string
   */
  public function getEtag()
  {
    return $this->etag;
  }
  /**
   * @param GitProxyConfig
   */
  public function setGitProxyConfig(GitProxyConfig $gitProxyConfig)
  {
    $this->gitProxyConfig = $gitProxyConfig;
  }
  /**
   * @return GitProxyConfig
   */
  public function getGitProxyConfig()
  {
    return $this->gitProxyConfig;
  }
  /**
   * @param GitHubConfig
   */
  public function setGithubConfig(GitHubConfig $githubConfig)
  {
    $this->githubConfig = $githubConfig;
  }
  /**
   * @return GitHubConfig
   */
  public function getGithubConfig()
  {
    return $this->githubConfig;
  }
  /**
   * @param GitHubEnterpriseConfig
   */
  public function setGithubEnterpriseConfig(GitHubEnterpriseConfig $githubEnterpriseConfig)
  {
    $this->githubEnterpriseConfig = $githubEnterpriseConfig;
  }
  /**
   * @return GitHubEnterpriseConfig
   */
  public function getGithubEnterpriseConfig()
  {
    return $this->githubEnterpriseConfig;
  }
  /**
   * @param GitLabConfig
   */
  public function setGitlabConfig(GitLabConfig $gitlabConfig)
  {
    $this->gitlabConfig = $gitlabConfig;
  }
  /**
   * @return GitLabConfig
   */
  public function getGitlabConfig()
  {
    return $this->gitlabConfig;
  }
  /**
   * @param GitLabEnterpriseConfig
   */
  public function setGitlabEnterpriseConfig(GitLabEnterpriseConfig $gitlabEnterpriseConfig)
  {
    $this->gitlabEnterpriseConfig = $gitlabEnterpriseConfig;
  }
  /**
   * @return GitLabEnterpriseConfig
   */
  public function getGitlabEnterpriseConfig()
  {
    return $this->gitlabEnterpriseConfig;
  }
  /**
   * @param InstallationState
   */
  public function setInstallationState(InstallationState $installationState)
  {
    $this->installationState = $installationState;
  }
  /**
   * @return InstallationState
   */
  public function getInstallationState()
  {
    return $this->installationState;
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
   * @param bool
   */
  public function setReconciling($reconciling)
  {
    $this->reconciling = $reconciling;
  }
  /**
   * @return bool
   */
  public function getReconciling()
  {
    return $this->reconciling;
  }
  /**
   * @param string
   */
  public function setUid($uid)
  {
    $this->uid = $uid;
  }
  /**
   * @return string
   */
  public function getUid()
  {
    return $this->uid;
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
class_alias(Connection::class, 'Google_Service_DeveloperConnect_Connection');
