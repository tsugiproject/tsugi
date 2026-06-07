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

namespace Google\Service\Dataproc;

class PySparkNotebookBatch extends \Google\Collection
{
  protected $collection_key = 'pythonFileUris';
  /**
   * Optional. HCFS URIs of archives to be extracted into the working directory
   * of each executor. Supported file types: .jar, .tar, .tar.gz, .tgz, and
   * .zip.
   *
   * @var string[]
   */
  public $archiveUris;
  /**
   * Optional. HCFS URIs of files to be placed in the working directory of each
   * executor
   *
   * @var string[]
   */
  public $fileUris;
  /**
   * Optional. HCFS URIs of jar files to be added to the Spark CLASSPATH.
   *
   * @var string[]
   */
  public $jarFileUris;
  /**
   * Required. The HCFS URI of the notebook file to execute.
   *
   * @var string
   */
  public $notebookFileUri;
  /**
   * Optional. The parameters to pass to the notebook.
   *
   * @var string[]
   */
  public $params;
  /**
   * Optional. HCFS URIs of Python files to pass to the PySpark framework.
   *
   * @var string[]
   */
  public $pythonFileUris;

  /**
   * Optional. HCFS URIs of archives to be extracted into the working directory
   * of each executor. Supported file types: .jar, .tar, .tar.gz, .tgz, and
   * .zip.
   *
   * @param string[] $archiveUris
   */
  public function setArchiveUris($archiveUris)
  {
    $this->archiveUris = $archiveUris;
  }
  /**
   * @return string[]
   */
  public function getArchiveUris()
  {
    return $this->archiveUris;
  }
  /**
   * Optional. HCFS URIs of files to be placed in the working directory of each
   * executor
   *
   * @param string[] $fileUris
   */
  public function setFileUris($fileUris)
  {
    $this->fileUris = $fileUris;
  }
  /**
   * @return string[]
   */
  public function getFileUris()
  {
    return $this->fileUris;
  }
  /**
   * Optional. HCFS URIs of jar files to be added to the Spark CLASSPATH.
   *
   * @param string[] $jarFileUris
   */
  public function setJarFileUris($jarFileUris)
  {
    $this->jarFileUris = $jarFileUris;
  }
  /**
   * @return string[]
   */
  public function getJarFileUris()
  {
    return $this->jarFileUris;
  }
  /**
   * Required. The HCFS URI of the notebook file to execute.
   *
   * @param string $notebookFileUri
   */
  public function setNotebookFileUri($notebookFileUri)
  {
    $this->notebookFileUri = $notebookFileUri;
  }
  /**
   * @return string
   */
  public function getNotebookFileUri()
  {
    return $this->notebookFileUri;
  }
  /**
   * Optional. The parameters to pass to the notebook.
   *
   * @param string[] $params
   */
  public function setParams($params)
  {
    $this->params = $params;
  }
  /**
   * @return string[]
   */
  public function getParams()
  {
    return $this->params;
  }
  /**
   * Optional. HCFS URIs of Python files to pass to the PySpark framework.
   *
   * @param string[] $pythonFileUris
   */
  public function setPythonFileUris($pythonFileUris)
  {
    $this->pythonFileUris = $pythonFileUris;
  }
  /**
   * @return string[]
   */
  public function getPythonFileUris()
  {
    return $this->pythonFileUris;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PySparkNotebookBatch::class, 'Google_Service_Dataproc_PySparkNotebookBatch');
