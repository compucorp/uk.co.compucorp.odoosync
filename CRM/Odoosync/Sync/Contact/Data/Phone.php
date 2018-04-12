<?php

/**
 * Prepares contact's phone, mobile and fax number for synchronization
 */
class CRM_Odoosync_Sync_Contact_Data_Phone extends CRM_Odoosync_Sync_Contact_Data_Data {

  /**
   * Retrieves phone, mobile and fax number
   *
   * @return array
   */
  public function retrieveData() {
    return [
      'numberPhone' => $this->getPhoneNumber('Phone'),
      'numberMobile' => $this->getPhoneNumber('Mobile'),
      'numberFax' => $this->getPhoneNumber('Fax'),
    ];
  }

  /**
   * Prepares contact's phone for synchronization
   *
   * @param string $phoneType
   *
   * @return array|string
   */
  private function getPhoneNumber($phoneType) {
    $number = $this->getPhoneByParam([
      'location_type_id' => "Billing",
      'phone_type_id' => $phoneType
    ]);

    if (!empty($number)) {
      return $number;
    }

    $number = $this->getPhoneByParam([
      'is_primary' => 1,
      'phone_type_id' => $phoneType
    ]);

    if (!empty($number)) {
      return $number;
    }

    $number = $this->getPhoneByParam([
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
   * @param array $param
   *
   * @return array|string
   */
  private function getPhoneByParam($param) {
    $ultimateParam = [
      'return' => "phone",
      'contact_id' => $this->contactId,
      'options' => ['limit' => 1],
      ] + $param;
    try {
      return civicrm_api3('Phone', 'getvalue', $ultimateParam);
    }
    catch (CiviCRM_API3_Exception $e) {
      return '';
    }
  }

}
