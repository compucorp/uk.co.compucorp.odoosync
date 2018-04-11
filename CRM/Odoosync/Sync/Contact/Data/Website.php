<?php

/**
 * Prepares contact's website url for synchronization
 */
class CRM_Odoosync_Sync_Contact_Data_Website extends CRM_Odoosync_Sync_Contact_Data_Data {

  /**
   * Prepares contact's website for synchronization
   *
   * @return string
   */
  public function retrieveData() {
    $param = [
      'return' => "url",
      'contact_id' => $this->contactId,
      'website_type_id' => "Main",
    ];
    $websiteUrl = $this->getWebsiteByParam($param);
    if (!empty($websiteUrl)) {
      return $websiteUrl;
    }

    $param = [
      'return' => "url",
      'contact_id' => $this->contactId,
    ];
    $websiteUrl = $this->getWebsiteByParam($param);
    if (!empty($websiteUrl)) {
      return $websiteUrl;
    }

    return '';
  }

  /**
   * Gets contact's website by special parameters
   *
   * @return array|string
   */
  private function getWebsiteByParam($param) {
    try {
      return civicrm_api3('Website', 'getvalue', $param);
    }
    catch (CiviCRM_API3_Exception $e) {
      return '';
    }
  }

}
