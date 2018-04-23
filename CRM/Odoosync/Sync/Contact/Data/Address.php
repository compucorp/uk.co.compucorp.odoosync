<?php

class CRM_Odoosync_Sync_Contact_Data_Address extends CRM_Odoosync_Sync_Contact_Data {

  /**
   * Retrieves the Contact address details
   *
   * @return array
   */
  public function retrieve() {
    $fieldsToSync = [
      'countryIsoCode' => '',
      'supplementalAddress' => ''
    ];

    $addressValues = $this->getAddressDataByLocationType('Billing');

    if (empty($addressValues)) {
      $addressValues = $this->getPrimaryAddressData();
    }

    $fieldsToSync['streetAddress'] = (!empty($addressValues['street_address'])) ? $addressValues['street_address'] : '';
    $fieldsToSync['city'] = (!empty($addressValues['city'])) ? $addressValues['city'] : '';
    $fieldsToSync['postalCode'] = (!empty($addressValues['postal_code'])) ? $addressValues['postal_code'] : '';

    if (!empty($addressValues)) {
      $fieldsToSync['countryIsoCode'] = $this->getCountryIsoCode($addressValues['country_id']);
      $fieldsToSync['supplementalAddress'] = $this->generateSupplementalAddress($addressValues);
    }

    return $fieldsToSync;
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
        'options' => ['limit' => 1],
        'contact_id' => $this->contactId,
        'location_type_id' => $locationType,
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      return [];
    }

    return (!empty($address['values'])) ? $address['values'][0] : [];
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
        'options' => ['limit' => 1],
        'contact_id' => $this->contactId,
        'is_primary' => 1,
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      return [];
    }

    return (!empty($address['values'])) ? $address['values'][0] : [];
  }

  /**
   * Gets supplemental address
   *
   * @param array $address
   *
   * @return string
   */
  private function generateSupplementalAddress($address) {
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
