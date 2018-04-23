<?php

class CRM_Odoosync_Sync_Contact_Data_Phone extends CRM_Odoosync_Sync_Contact_Data {

  /**
   * Retrieves phone, mobile and fax number
   *
   * @return array
   */
  public function retrieve() {
    return [
      'phoneNumber' => $this->getPhoneNumberByType('Phone'),
      'mobileNumber' => $this->getPhoneNumberByType('Mobile'),
      'faxNumber' => $this->getPhoneNumberByType('Fax'),
    ];
  }

  /**
   * Prepares contact's phone for synchronization
   *
   * @param string $phoneType
   *
   * @return string
   */
  private function getPhoneNumberByType($phoneType) {
    $number = $this->getPhoneNumber([
      'location_type_id' => "Billing",
      'phone_type_id' => $phoneType
    ]);

    if (!empty($number)) {
      return $number;
    }

    $number = $this->getPhoneNumber([
      'is_primary' => 1,
      'phone_type_id' => $phoneType
    ]);

    if (!empty($number)) {
      return $number;
    }

    $number = $this->getPhoneNumber([
      'phone_type_id' => $phoneType
    ]);

    if (!empty($number)) {
      return $number;
    }

    return '';
  }

  /**
   * Gets contact's phone by special parameters
   *
   * @param array $additionalParams
   *
   * @return string
   */
  private function getPhoneNumber($additionalParams) {
    $defaultParams = [
      'return' => "phone",
      'contact_id' => $this->contactId,
      'options' => ['limit' => 1],
      ];
    $params = array_merge($defaultParams, $additionalParams);

    try {
      return civicrm_api3('Phone', 'getvalue', $params);
    }
    catch (CiviCRM_API3_Exception $e) {
      return '';
    }
  }

}
