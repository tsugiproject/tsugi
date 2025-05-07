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

namespace Google\Service\Walletobjects;

class FlightClass extends \Google\Collection
{
  protected $collection_key = 'valueAddedModuleData';
  /**
   * @var bool
   */
  public $allowMultipleUsersPerObject;
  protected $appLinkDataType = AppLinkData::class;
  protected $appLinkDataDataType = '';
  protected $boardingAndSeatingPolicyType = BoardingAndSeatingPolicy::class;
  protected $boardingAndSeatingPolicyDataType = '';
  protected $callbackOptionsType = CallbackOptions::class;
  protected $callbackOptionsDataType = '';
  protected $classTemplateInfoType = ClassTemplateInfo::class;
  protected $classTemplateInfoDataType = '';
  /**
   * @var string
   */
  public $countryCode;
  protected $destinationType = AirportInfo::class;
  protected $destinationDataType = '';
  /**
   * @var bool
   */
  public $enableSmartTap;
  protected $flightHeaderType = FlightHeader::class;
  protected $flightHeaderDataType = '';
  /**
   * @var string
   */
  public $flightStatus;
  protected $heroImageType = Image::class;
  protected $heroImageDataType = '';
  /**
   * @var string
   */
  public $hexBackgroundColor;
  protected $homepageUriType = Uri::class;
  protected $homepageUriDataType = '';
  /**
   * @var string
   */
  public $id;
  protected $imageModulesDataType = ImageModuleData::class;
  protected $imageModulesDataDataType = 'array';
  protected $infoModuleDataType = InfoModuleData::class;
  protected $infoModuleDataDataType = '';
  /**
   * @var string
   */
  public $issuerName;
  /**
   * @var string
   */
  public $kind;
  /**
   * @var string
   */
  public $languageOverride;
  protected $linksModuleDataType = LinksModuleData::class;
  protected $linksModuleDataDataType = '';
  /**
   * @var string
   */
  public $localBoardingDateTime;
  /**
   * @var string
   */
  public $localEstimatedOrActualArrivalDateTime;
  /**
   * @var string
   */
  public $localEstimatedOrActualDepartureDateTime;
  /**
   * @var string
   */
  public $localGateClosingDateTime;
  /**
   * @var string
   */
  public $localScheduledArrivalDateTime;
  /**
   * @var string
   */
  public $localScheduledDepartureDateTime;
  protected $localizedIssuerNameType = LocalizedString::class;
  protected $localizedIssuerNameDataType = '';
  protected $locationsType = LatLongPoint::class;
  protected $locationsDataType = 'array';
  protected $merchantLocationsType = MerchantLocation::class;
  protected $merchantLocationsDataType = 'array';
  protected $messagesType = Message::class;
  protected $messagesDataType = 'array';
  /**
   * @var string
   */
  public $multipleDevicesAndHoldersAllowedStatus;
  /**
   * @var string
   */
  public $notifyPreference;
  protected $originType = AirportInfo::class;
  protected $originDataType = '';
  /**
   * @var string[]
   */
  public $redemptionIssuers;
  protected $reviewType = Review::class;
  protected $reviewDataType = '';
  /**
   * @var string
   */
  public $reviewStatus;
  protected $securityAnimationType = SecurityAnimation::class;
  protected $securityAnimationDataType = '';
  protected $textModulesDataType = TextModuleData::class;
  protected $textModulesDataDataType = 'array';
  protected $valueAddedModuleDataType = ValueAddedModuleData::class;
  protected $valueAddedModuleDataDataType = 'array';
  /**
   * @var string
   */
  public $version;
  /**
   * @var string
   */
  public $viewUnlockRequirement;
  protected $wordMarkType = Image::class;
  protected $wordMarkDataType = '';

  /**
   * @param bool
   */
  public function setAllowMultipleUsersPerObject($allowMultipleUsersPerObject)
  {
    $this->allowMultipleUsersPerObject = $allowMultipleUsersPerObject;
  }
  /**
   * @return bool
   */
  public function getAllowMultipleUsersPerObject()
  {
    return $this->allowMultipleUsersPerObject;
  }
  /**
   * @param AppLinkData
   */
  public function setAppLinkData(AppLinkData $appLinkData)
  {
    $this->appLinkData = $appLinkData;
  }
  /**
   * @return AppLinkData
   */
  public function getAppLinkData()
  {
    return $this->appLinkData;
  }
  /**
   * @param BoardingAndSeatingPolicy
   */
  public function setBoardingAndSeatingPolicy(BoardingAndSeatingPolicy $boardingAndSeatingPolicy)
  {
    $this->boardingAndSeatingPolicy = $boardingAndSeatingPolicy;
  }
  /**
   * @return BoardingAndSeatingPolicy
   */
  public function getBoardingAndSeatingPolicy()
  {
    return $this->boardingAndSeatingPolicy;
  }
  /**
   * @param CallbackOptions
   */
  public function setCallbackOptions(CallbackOptions $callbackOptions)
  {
    $this->callbackOptions = $callbackOptions;
  }
  /**
   * @return CallbackOptions
   */
  public function getCallbackOptions()
  {
    return $this->callbackOptions;
  }
  /**
   * @param ClassTemplateInfo
   */
  public function setClassTemplateInfo(ClassTemplateInfo $classTemplateInfo)
  {
    $this->classTemplateInfo = $classTemplateInfo;
  }
  /**
   * @return ClassTemplateInfo
   */
  public function getClassTemplateInfo()
  {
    return $this->classTemplateInfo;
  }
  /**
   * @param string
   */
  public function setCountryCode($countryCode)
  {
    $this->countryCode = $countryCode;
  }
  /**
   * @return string
   */
  public function getCountryCode()
  {
    return $this->countryCode;
  }
  /**
   * @param AirportInfo
   */
  public function setDestination(AirportInfo $destination)
  {
    $this->destination = $destination;
  }
  /**
   * @return AirportInfo
   */
  public function getDestination()
  {
    return $this->destination;
  }
  /**
   * @param bool
   */
  public function setEnableSmartTap($enableSmartTap)
  {
    $this->enableSmartTap = $enableSmartTap;
  }
  /**
   * @return bool
   */
  public function getEnableSmartTap()
  {
    return $this->enableSmartTap;
  }
  /**
   * @param FlightHeader
   */
  public function setFlightHeader(FlightHeader $flightHeader)
  {
    $this->flightHeader = $flightHeader;
  }
  /**
   * @return FlightHeader
   */
  public function getFlightHeader()
  {
    return $this->flightHeader;
  }
  /**
   * @param string
   */
  public function setFlightStatus($flightStatus)
  {
    $this->flightStatus = $flightStatus;
  }
  /**
   * @return string
   */
  public function getFlightStatus()
  {
    return $this->flightStatus;
  }
  /**
   * @param Image
   */
  public function setHeroImage(Image $heroImage)
  {
    $this->heroImage = $heroImage;
  }
  /**
   * @return Image
   */
  public function getHeroImage()
  {
    return $this->heroImage;
  }
  /**
   * @param string
   */
  public function setHexBackgroundColor($hexBackgroundColor)
  {
    $this->hexBackgroundColor = $hexBackgroundColor;
  }
  /**
   * @return string
   */
  public function getHexBackgroundColor()
  {
    return $this->hexBackgroundColor;
  }
  /**
   * @param Uri
   */
  public function setHomepageUri(Uri $homepageUri)
  {
    $this->homepageUri = $homepageUri;
  }
  /**
   * @return Uri
   */
  public function getHomepageUri()
  {
    return $this->homepageUri;
  }
  /**
   * @param string
   */
  public function setId($id)
  {
    $this->id = $id;
  }
  /**
   * @return string
   */
  public function getId()
  {
    return $this->id;
  }
  /**
   * @param ImageModuleData[]
   */
  public function setImageModulesData($imageModulesData)
  {
    $this->imageModulesData = $imageModulesData;
  }
  /**
   * @return ImageModuleData[]
   */
  public function getImageModulesData()
  {
    return $this->imageModulesData;
  }
  /**
   * @param InfoModuleData
   */
  public function setInfoModuleData(InfoModuleData $infoModuleData)
  {
    $this->infoModuleData = $infoModuleData;
  }
  /**
   * @return InfoModuleData
   */
  public function getInfoModuleData()
  {
    return $this->infoModuleData;
  }
  /**
   * @param string
   */
  public function setIssuerName($issuerName)
  {
    $this->issuerName = $issuerName;
  }
  /**
   * @return string
   */
  public function getIssuerName()
  {
    return $this->issuerName;
  }
  /**
   * @param string
   */
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  /**
   * @return string
   */
  public function getKind()
  {
    return $this->kind;
  }
  /**
   * @param string
   */
  public function setLanguageOverride($languageOverride)
  {
    $this->languageOverride = $languageOverride;
  }
  /**
   * @return string
   */
  public function getLanguageOverride()
  {
    return $this->languageOverride;
  }
  /**
   * @param LinksModuleData
   */
  public function setLinksModuleData(LinksModuleData $linksModuleData)
  {
    $this->linksModuleData = $linksModuleData;
  }
  /**
   * @return LinksModuleData
   */
  public function getLinksModuleData()
  {
    return $this->linksModuleData;
  }
  /**
   * @param string
   */
  public function setLocalBoardingDateTime($localBoardingDateTime)
  {
    $this->localBoardingDateTime = $localBoardingDateTime;
  }
  /**
   * @return string
   */
  public function getLocalBoardingDateTime()
  {
    return $this->localBoardingDateTime;
  }
  /**
   * @param string
   */
  public function setLocalEstimatedOrActualArrivalDateTime($localEstimatedOrActualArrivalDateTime)
  {
    $this->localEstimatedOrActualArrivalDateTime = $localEstimatedOrActualArrivalDateTime;
  }
  /**
   * @return string
   */
  public function getLocalEstimatedOrActualArrivalDateTime()
  {
    return $this->localEstimatedOrActualArrivalDateTime;
  }
  /**
   * @param string
   */
  public function setLocalEstimatedOrActualDepartureDateTime($localEstimatedOrActualDepartureDateTime)
  {
    $this->localEstimatedOrActualDepartureDateTime = $localEstimatedOrActualDepartureDateTime;
  }
  /**
   * @return string
   */
  public function getLocalEstimatedOrActualDepartureDateTime()
  {
    return $this->localEstimatedOrActualDepartureDateTime;
  }
  /**
   * @param string
   */
  public function setLocalGateClosingDateTime($localGateClosingDateTime)
  {
    $this->localGateClosingDateTime = $localGateClosingDateTime;
  }
  /**
   * @return string
   */
  public function getLocalGateClosingDateTime()
  {
    return $this->localGateClosingDateTime;
  }
  /**
   * @param string
   */
  public function setLocalScheduledArrivalDateTime($localScheduledArrivalDateTime)
  {
    $this->localScheduledArrivalDateTime = $localScheduledArrivalDateTime;
  }
  /**
   * @return string
   */
  public function getLocalScheduledArrivalDateTime()
  {
    return $this->localScheduledArrivalDateTime;
  }
  /**
   * @param string
   */
  public function setLocalScheduledDepartureDateTime($localScheduledDepartureDateTime)
  {
    $this->localScheduledDepartureDateTime = $localScheduledDepartureDateTime;
  }
  /**
   * @return string
   */
  public function getLocalScheduledDepartureDateTime()
  {
    return $this->localScheduledDepartureDateTime;
  }
  /**
   * @param LocalizedString
   */
  public function setLocalizedIssuerName(LocalizedString $localizedIssuerName)
  {
    $this->localizedIssuerName = $localizedIssuerName;
  }
  /**
   * @return LocalizedString
   */
  public function getLocalizedIssuerName()
  {
    return $this->localizedIssuerName;
  }
  /**
   * @param LatLongPoint[]
   */
  public function setLocations($locations)
  {
    $this->locations = $locations;
  }
  /**
   * @return LatLongPoint[]
   */
  public function getLocations()
  {
    return $this->locations;
  }
  /**
   * @param MerchantLocation[]
   */
  public function setMerchantLocations($merchantLocations)
  {
    $this->merchantLocations = $merchantLocations;
  }
  /**
   * @return MerchantLocation[]
   */
  public function getMerchantLocations()
  {
    return $this->merchantLocations;
  }
  /**
   * @param Message[]
   */
  public function setMessages($messages)
  {
    $this->messages = $messages;
  }
  /**
   * @return Message[]
   */
  public function getMessages()
  {
    return $this->messages;
  }
  /**
   * @param string
   */
  public function setMultipleDevicesAndHoldersAllowedStatus($multipleDevicesAndHoldersAllowedStatus)
  {
    $this->multipleDevicesAndHoldersAllowedStatus = $multipleDevicesAndHoldersAllowedStatus;
  }
  /**
   * @return string
   */
  public function getMultipleDevicesAndHoldersAllowedStatus()
  {
    return $this->multipleDevicesAndHoldersAllowedStatus;
  }
  /**
   * @param string
   */
  public function setNotifyPreference($notifyPreference)
  {
    $this->notifyPreference = $notifyPreference;
  }
  /**
   * @return string
   */
  public function getNotifyPreference()
  {
    return $this->notifyPreference;
  }
  /**
   * @param AirportInfo
   */
  public function setOrigin(AirportInfo $origin)
  {
    $this->origin = $origin;
  }
  /**
   * @return AirportInfo
   */
  public function getOrigin()
  {
    return $this->origin;
  }
  /**
   * @param string[]
   */
  public function setRedemptionIssuers($redemptionIssuers)
  {
    $this->redemptionIssuers = $redemptionIssuers;
  }
  /**
   * @return string[]
   */
  public function getRedemptionIssuers()
  {
    return $this->redemptionIssuers;
  }
  /**
   * @param Review
   */
  public function setReview(Review $review)
  {
    $this->review = $review;
  }
  /**
   * @return Review
   */
  public function getReview()
  {
    return $this->review;
  }
  /**
   * @param string
   */
  public function setReviewStatus($reviewStatus)
  {
    $this->reviewStatus = $reviewStatus;
  }
  /**
   * @return string
   */
  public function getReviewStatus()
  {
    return $this->reviewStatus;
  }
  /**
   * @param SecurityAnimation
   */
  public function setSecurityAnimation(SecurityAnimation $securityAnimation)
  {
    $this->securityAnimation = $securityAnimation;
  }
  /**
   * @return SecurityAnimation
   */
  public function getSecurityAnimation()
  {
    return $this->securityAnimation;
  }
  /**
   * @param TextModuleData[]
   */
  public function setTextModulesData($textModulesData)
  {
    $this->textModulesData = $textModulesData;
  }
  /**
   * @return TextModuleData[]
   */
  public function getTextModulesData()
  {
    return $this->textModulesData;
  }
  /**
   * @param ValueAddedModuleData[]
   */
  public function setValueAddedModuleData($valueAddedModuleData)
  {
    $this->valueAddedModuleData = $valueAddedModuleData;
  }
  /**
   * @return ValueAddedModuleData[]
   */
  public function getValueAddedModuleData()
  {
    return $this->valueAddedModuleData;
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
  /**
   * @param string
   */
  public function setViewUnlockRequirement($viewUnlockRequirement)
  {
    $this->viewUnlockRequirement = $viewUnlockRequirement;
  }
  /**
   * @return string
   */
  public function getViewUnlockRequirement()
  {
    return $this->viewUnlockRequirement;
  }
  /**
   * @param Image
   */
  public function setWordMark(Image $wordMark)
  {
    $this->wordMark = $wordMark;
  }
  /**
   * @return Image
   */
  public function getWordMark()
  {
    return $this->wordMark;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(FlightClass::class, 'Google_Service_Walletobjects_FlightClass');
