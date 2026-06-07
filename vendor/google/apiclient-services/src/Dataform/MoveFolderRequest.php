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

class MoveFolderRequest extends \Google\Model
{
  /**
   * Optional. The name of the Folder, TeamFolder, or root location to move the
   * Folder to. Can be in the format of: "" to move into the root User folder,
   * `projects/locations/folders`, `projects/locations/teamFolders`
   *
   * @var string
   */
  public $destinationContainingFolder;

  /**
   * Optional. The name of the Folder, TeamFolder, or root location to move the
   * Folder to. Can be in the format of: "" to move into the root User folder,
   * `projects/locations/folders`, `projects/locations/teamFolders`
   *
   * @param string $destinationContainingFolder
   */
  public function setDestinationContainingFolder($destinationContainingFolder)
  {
    $this->destinationContainingFolder = $destinationContainingFolder;
  }
  /**
   * @return string
   */
  public function getDestinationContainingFolder()
  {
    return $this->destinationContainingFolder;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(MoveFolderRequest::class, 'Google_Service_Dataform_MoveFolderRequest');
