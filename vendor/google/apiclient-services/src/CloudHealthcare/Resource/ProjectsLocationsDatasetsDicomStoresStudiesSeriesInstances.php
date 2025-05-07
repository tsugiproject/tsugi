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

namespace Google\Service\CloudHealthcare\Resource;

use Google\Service\CloudHealthcare\HealthcareEmpty;
use Google\Service\CloudHealthcare\HttpBody;

/**
 * The "instances" collection of methods.
 * Typical usage is:
 *  <code>
 *   $healthcareService = new Google\Service\CloudHealthcare(...);
 *   $instances = $healthcareService->projects_locations_datasets_dicomStores_studies_series_instances;
 *  </code>
 */
class ProjectsLocationsDatasetsDicomStoresStudiesSeriesInstances extends \Google\Service\Resource
{
  /**
   * DeleteInstance deletes an instance associated with the given study, series,
   * and SOP Instance UID. Delete requests are equivalent to the GET requests
   * specified in the Retrieve transaction. Study and series search results can
   * take a few seconds to be updated after an instance is deleted using
   * DeleteInstance. For samples that show how to call DeleteInstance, see [Delete
   * a study, series, or instance](https://cloud.google.com/healthcare/docs/how-
   * tos/dicomweb#delete-dicom). (instances.delete)
   *
   * @param string $parent Required. The name of the DICOM store that is being
   * accessed. For example, `projects/{project_id}/locations/{location_id}/dataset
   * s/{dataset_id}/dicomStores/{dicom_store_id}`.
   * @param string $dicomWebPath Required. The path of the DeleteInstance request.
   * For example,
   * `studies/{study_uid}/series/{series_uid}/instances/{instance_uid}`.
   * @param array $optParams Optional parameters.
   * @return HealthcareEmpty
   * @throws \Google\Service\Exception
   */
  public function delete($parent, $dicomWebPath, $optParams = [])
  {
    $params = ['parent' => $parent, 'dicomWebPath' => $dicomWebPath];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], HealthcareEmpty::class);
  }
  /**
   * RetrieveInstance returns instance associated with the given study, series,
   * and SOP Instance UID. See [RetrieveTransaction] (http://dicom.nema.org/medica
   * l/dicom/current/output/html/part18.html#sect_10.4). For details on the
   * implementation of RetrieveInstance, see [DICOM study/series/instances](https:
   * //cloud.google.com/healthcare/docs/dicom#dicom_studyseriesinstances) and
   * [DICOM
   * instances](https://cloud.google.com/healthcare/docs/dicom#dicom_instances) in
   * the Cloud Healthcare API conformance statement. For samples that show how to
   * call RetrieveInstance, see [Retrieve an
   * instance](https://cloud.google.com/healthcare/docs/how-tos/dicomweb#retrieve-
   * instance). (instances.retrieveInstance)
   *
   * @param string $parent Required. The name of the DICOM store that is being
   * accessed. For example, `projects/{project_id}/locations/{location_id}/dataset
   * s/{dataset_id}/dicomStores/{dicom_store_id}`.
   * @param string $dicomWebPath Required. The path of the RetrieveInstance
   * DICOMweb request. For example,
   * `studies/{study_uid}/series/{series_uid}/instances/{instance_uid}`.
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function retrieveInstance($parent, $dicomWebPath, $optParams = [])
  {
    $params = ['parent' => $parent, 'dicomWebPath' => $dicomWebPath];
    $params = array_merge($params, $optParams);
    return $this->call('retrieveInstance', [$params], HttpBody::class);
  }
  /**
   * RetrieveInstanceMetadata returns instance associated with the given study,
   * series, and SOP Instance UID presented as metadata with the bulk data
   * removed. See [RetrieveTransaction] (http://dicom.nema.org/medical/dicom/curre
   * nt/output/html/part18.html#sect_10.4). For details on the implementation of
   * RetrieveInstanceMetadata, see [Metadata
   * resources](https://cloud.google.com/healthcare/docs/dicom#metadata_resources)
   * in the Cloud Healthcare API conformance statement. For samples that show how
   * to call RetrieveInstanceMetadata, see [Retrieve
   * metadata](https://cloud.google.com/healthcare/docs/how-tos/dicomweb#retrieve-
   * metadata). (instances.retrieveMetadata)
   *
   * @param string $parent Required. The name of the DICOM store that is being
   * accessed. For example, `projects/{project_id}/locations/{location_id}/dataset
   * s/{dataset_id}/dicomStores/{dicom_store_id}`.
   * @param string $dicomWebPath Required. The path of the
   * RetrieveInstanceMetadata DICOMweb request. For example,
   * `studies/{study_uid}/series/{series_uid}/instances/{instance_uid}/metadata`.
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function retrieveMetadata($parent, $dicomWebPath, $optParams = [])
  {
    $params = ['parent' => $parent, 'dicomWebPath' => $dicomWebPath];
    $params = array_merge($params, $optParams);
    return $this->call('retrieveMetadata', [$params], HttpBody::class);
  }
  /**
   * RetrieveRenderedInstance returns instance associated with the given study,
   * series, and SOP Instance UID in an acceptable Rendered Media Type. See
   * [RetrieveTransaction] (http://dicom.nema.org/medical/dicom/current/output/htm
   * l/part18.html#sect_10.4). For details on the implementation of
   * RetrieveRenderedInstance, see [Rendered
   * resources](https://cloud.google.com/healthcare/docs/dicom#rendered_resources)
   * in the Cloud Healthcare API conformance statement. For samples that show how
   * to call RetrieveRenderedInstance, see [Retrieve consumer image
   * formats](https://cloud.google.com/healthcare/docs/how-tos/dicomweb#retrieve-
   * consumer). (instances.retrieveRendered)
   *
   * @param string $parent Required. The name of the DICOM store that is being
   * accessed. For example, `projects/{project_id}/locations/{location_id}/dataset
   * s/{dataset_id}/dicomStores/{dicom_store_id}`.
   * @param string $dicomWebPath Required. The path of the
   * RetrieveRenderedInstance DICOMweb request. For example,
   * `studies/{study_uid}/series/{series_uid}/instances/{instance_uid}/rendered`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string viewport Optional. The viewport setting to use as specified
   * in https://dicom.nema.org/medical/dicom/current/output/chtml/part18/sect_8.3.
   * 5.html#sect_8.3.5.1.3
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function retrieveRendered($parent, $dicomWebPath, $optParams = [])
  {
    $params = ['parent' => $parent, 'dicomWebPath' => $dicomWebPath];
    $params = array_merge($params, $optParams);
    return $this->call('retrieveRendered', [$params], HttpBody::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsDatasetsDicomStoresStudiesSeriesInstances::class, 'Google_Service_CloudHealthcare_Resource_ProjectsLocationsDatasetsDicomStoresStudiesSeriesInstances');
