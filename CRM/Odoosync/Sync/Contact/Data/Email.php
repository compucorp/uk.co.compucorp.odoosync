<?php

class CRM_Odoosync_Sync_Contact_Data_Email extends CRM_Odoosync_Sync_Contact_Data {

  /**
   * Retrieves the Contact email address for sync
   *
   * @return string
   */
  public function retrieve() {
    $email = $this->getEmail(['location_type_id' => "Billing"]);
    if (!empty($email)) {
      return $email;
    }

    $email = $this->getEmail(['is_primary' => 1]);
    if (!empty($email)) {
      return $email;
    }

    $email = $this->getEmail(['location_type_id' => "Main"]);
    if (!empty($email)) {
      return $email;
    }

    $email = $this->getEmail([]);
    if (!empty($email)) {
      return $email;
    }

    return '';
  }

  /**
   * Gets the contact's email address according the specified parameters
   *
   * @param array $additionalParams
   *
   * @return string
   */
  private function getEmail($additionalParams) {
    $defaultParams = [
      'return' => "email",
      'contact_id' => $this->contactId,
      'options' => ['limit' => 1],
      ];
    $params = array_merge($defaultParams, $additionalParams);

    try {
      return civicrm_api3('Email', 'getvalue', $params);
    }
    catch (CiviCRM_API3_Exception $e) {
      return '';
    }
  }

}
