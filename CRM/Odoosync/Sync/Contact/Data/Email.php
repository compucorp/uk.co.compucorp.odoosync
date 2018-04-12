<?php

/**
 * Retrieves prepared contact’s email for synchronization
 */
class CRM_Odoosync_Sync_Contact_Data_Email extends CRM_Odoosync_Sync_Contact_Data_Data {

  /**
   * Prepares a contact’s email for synchronization
   *
   * @return string
   */
  public function retrieveData() {
    $email = $this->getEmailByParam(['location_type_id' => "Billing"]);
    if (!empty($email)) {
      return $email;
    }

    $email = $this->getEmailByParam(['is_primary' => 1]);
    if (!empty($email)) {
      return $email;
    }

    $email = $this->getEmailByParam(['location_type_id' => "Main"]);
    if (!empty($email)) {
      return $email;
    }

    $email = $this->getEmailByParam([]);
    if (!empty($email)) {
      return $email;
    }

    return '';
  }

  /**
   * Gets contact's email by special parameters
   *
   * @param array $param
   *
   * @return mixed
   */
  private function getEmailByParam($param) {
    $ultimateParam = [
      'return' => "email",
      'contact_id' => $this->contactId,
      'options' => ['limit' => 1],
      ];
    $ultimateParam += $param;

    try {
      return civicrm_api3('Email', 'getvalue', $ultimateParam);
    }
    catch (CiviCRM_API3_Exception $e) {
      return '';
    }
  }

}
