<?php

/**
 * Retrieves address data: street address, supplemental address,
 * city, postal code, country ISO code
 */
class CRM_Odoosync_Sync_Contact_Data_Address extends CRM_Odoosync_Sync_Contact_Data_Data {

  /**
   * List of the address fields to be generated
   *
   * @var array
   */
  private $addressData = [
    'streetAddress' => '',
    'supplementalAddress' => '',
    'city' => '',
    'postalCode' => '',
    'countryIsoCode' => '',
  ];

  /**
   * Retrieves address data: street address, supplemental address,
   * city, postal code, country ISO code
   *
   * @return array
   */
  public function retrieveData() {
    $addressValues = $this->getAddressDataByLocationType('Billing');

    if (empty($addressValues)) {
      $addressValues = $this->getPrimaryAddressData();
    }

    if (!empty($addressValues)) {
      $this->addressData['streetAddress'] = $addressValues['street_address'];
      $this->addressData['supplementalAddress'] = $this->getSupplementalAddress($addressValues);
      $this->addressData['city'] = $addressValues['city'];
      $this->addressData['postalCode'] = $addressValues['postal_code'];
      $this->addressData['countryIsoCode'] = $this->getCountryIsoCode($addressValues['country_id']);
    }

    return $this->addressData;
  }

  /**
   * Gets address data by location type
   *
   * @param string $locationType
   *
   * @return array
   */
  private function getAddressDataByLocationType($locationType) {

    try {
      $address = civicrm_api3('Address', 'get', [
        'sequential' => 1,
        'contact_id' => $this->contactId,
        'location_type_id' => $locationType,
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      return [];
    }

    return $address['values'][0];
  }

  /**
   * Gets primary address data
   *
   * @return array
   */
  private function getPrimaryAddressData() {

    try {
      $address = civicrm_api3('Address', 'get', [
        'sequential' => 1,
        'contact_id' => $this->contactId,
        'is_primary' => 1,
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      return [];
    }

    return $address['values'][0];
  }

  /**
   * Gets supplemental address
   *
   * @param array $address
   *
   * @return string
   */
  private function getSupplementalAddress($address) {
    $supplementalAddress = '';

    if (!empty($address['supplemental_address_1'])) {
      $supplementalAddress .= $address['supplemental_address_1'] . ';';
    }

    if (!empty($address['supplemental_address_2'])) {
      $supplementalAddress .= $address['supplemental_address_2'] . ';';
    }

    if (!empty($address['supplemental_address_3'])) {
      $supplementalAddress .= $address['supplemental_address_3'] . ';';
    }

    if (!empty($address['state_province_id'])) {
      $supplementalAddress .= CRM_Core_PseudoConstant::stateProvince($address['state_province_id']) . ';';
    }

    return $supplementalAddress;
  }

  /**
   * Gets country ISO code by CiviCRM country id
   *
   * @param int $civiCountryId
   *
   * @return mixed
   */
  private function getCountryIsoCode($civiCountryId) {
    if (empty($civiCountryId)) {
      return '';
    }

    return CRM_Core_PseudoConstant::countryIsoCode($civiCountryId);
  }

}
