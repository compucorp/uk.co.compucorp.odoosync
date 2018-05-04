<?php

class CRM_Odoosync_Sync_Contact_Data_Website extends CRM_Odoosync_Sync_Contact_Data {

  /**
   * Prepares contact's website for synchronization
   *
   * @return string
   */
  public function retrieve() {
    $param = ['website_type_id' => "Main"];
    $websiteUrl = $this->getWebsiteURL($param);
    if (!empty($websiteUrl)) {
      return $websiteUrl;
    }

    $websiteUrl = $this->getWebsiteURL($param);
    if (!empty($websiteUrl)) {
      return $websiteUrl;
    }

    return '';
  }

  /**
   * Gets contact's website by special parameters
   *
   * @param array $additionalParams
   *
   * @return string
   */
  private function getWebsiteURL($additionalParams = []) {
    $defaultParams = [
      'return' => 'url',
      'options' => ['limit' => 1],
      'contact_id' => $this->contactId,
      ];
    $params = array_merge($defaultParams, $additionalParams);

    try {
      return civicrm_api3('Website', 'getvalue', $params);
    }
    catch (CiviCRM_API3_Exception $e) {
      return '';
    }
  }

}
