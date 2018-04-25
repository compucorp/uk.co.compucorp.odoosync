<?php

/**
 * Updates contact sync information when sync successful/error
 */
class CRM_Odoosync_Sync_Contact_SyncInformation {

  /**
   * Updates contact sync information when synchronization was successful
   *
   * @param int $partnerId
   * @param int $contactId
   */
  public function updateContactSuccessfulSync($partnerId, $contactId) {
    $query = "
      UPDATE odoo_partner_sync_information AS sync_info
        JOIN civicrm_option_group AS status_option_group 
          ON status_option_group.name = 'odoo_sync_status'
        LEFT JOIN civicrm_option_value AS status_option 
          ON status_option.option_group_id = status_option_group.id
          AND status_option.name = %2   
      SET
        sync_info.last_successful_sync_date = NOW(),
        sync_info.sync_status = status_option.value,
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
      2 => ['synced' , 'String'],
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
   */
  public function updateContactErrorSync($errorMessage, $retryThreshold, $contactId) {
    $retryCount = $this->getRetryCount($contactId);
    $newRetryCount = $retryCount + 1;
    $isReachedRetryThreshold = ($newRetryCount >= $retryThreshold);

    $queryParam = [
      1 => [$contactId, 'Integer'],
      3 => [$errorMessage , 'String'],
      2 => [(int) $newRetryCount , 'Integer'],
    ];

    if ($isReachedRetryThreshold) {
      $query = "
        UPDATE odoo_partner_sync_information AS sync_info
          JOIN civicrm_option_group AS status_option_group 
            ON status_option_group.name = 'odoo_sync_status'
          LEFT JOIN civicrm_option_value AS status_option 
            ON status_option.option_group_id = status_option_group.id
            AND status_option.name = %4 
        SET
          sync_info.last_retry = NOW(),
          sync_info.error_log = %3,
          sync_info.retry_count = %2,
          sync_info.sync_status = status_option.value
        WHERE entity_id = %1
      ";
      $queryParam[4] = ['sync_failed' , 'String'];
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

}
