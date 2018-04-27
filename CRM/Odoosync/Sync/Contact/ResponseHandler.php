<?php

/**
 * Updates contact sync information when sync successful/error
 */
class CRM_Odoosync_Sync_Contact_ResponseHandler {

  /**
   * Updates contact sync information when synchronization was successful
   *
   * @param int $partnerId
   * @param int $contactId
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function handleSuccess($partnerId, $contactId) {
    $syncStatusId = $this->getOptionValueID('odoo_sync_status', 'synced');
    $query = "
      UPDATE odoo_partner_sync_information AS sync_info 
      SET
        sync_info.last_successful_sync_date = NOW(),
        sync_info.sync_status = %2,
        sync_info.odoo_partner_id = %3,
        sync_info.action_to_sync = NULL,
        sync_info.action_date = NULL,
        sync_info.last_retry = NULL,
        sync_info.retry_count = 0,
        sync_info.error_log = NULL
      WHERE entity_id = %1 
    ";

    CRM_Core_DAO::executeQuery($query, [
      1 => [$contactId, 'Integer'],
      2 => [$syncStatusId , 'String'],
      3 => [(int) $partnerId , 'Integer']
    ]);
  }

  /**
   * Updates contact sync information when synchronization was failed
   *
   * @param string $errorMessage
   * @param int $retryThreshold
   * @param int $contactId
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public function handleError($errorMessage, $retryThreshold, $contactId) {
    $retryCount = $this->getRetryCount($contactId);
    $newRetryCount = $retryCount + 1;
    $isReachedRetryThreshold = ($newRetryCount >= $retryThreshold);

    $queryParam = [
      1 => [$contactId, 'Integer'],
      3 => [$errorMessage , 'String'],
      2 => [(int) $newRetryCount , 'Integer'],
    ];

    if ($isReachedRetryThreshold) {
      $syncFailedStatusId = $this->getOptionValueID('odoo_sync_status', 'sync_failed');
      $query = "
        UPDATE odoo_partner_sync_information AS sync_info
        SET
          sync_info.last_retry = NOW(),
          sync_info.error_log = %3,
          sync_info.retry_count = %2,
          sync_info.sync_status = %4
        WHERE entity_id = %1
      ";
      $queryParam[4] = [$syncFailedStatusId , 'String'];
    }
    else {
      $query = "
        UPDATE odoo_partner_sync_information AS sync_info
        SET
          sync_info.last_retry = NOW(),
          sync_info.error_log = %3,
          sync_info.retry_count = %2
        WHERE sync_info.entity_id = %1
      ";
    }

    CRM_Core_DAO::executeQuery($query, $queryParam);

    return $isReachedRetryThreshold;
  }

  /**
   * Gets contact's retry count
   *
   * @param int $contactId
   *
   * @return int
   */
  private function getRetryCount($contactId) {
    $query = "SELECT retry_count FROM odoo_partner_sync_information WHERE entity_id = %1 LIMIT 1";
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$contactId, 'Integer']]);

    while ($dao->fetch()) {
      return (int) $dao->retry_count;
    }

    return 0;
  }

  /**
   * Gets the specified option value ID (value)
   * for the specified option group.
   *
   * @param string $optionGroupName
   * @param string $optionValueName
   *
   * @return string
   * @throws \CiviCRM_API3_Exception
   */
  private function getOptionValueID($optionGroupName, $optionValueName) {
    $value = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => ["value"],
      'option_group_id' => $optionGroupName,
      'name' => $optionValueName,
    ]);

    return $value['values'][0]['value'];
  }

}
