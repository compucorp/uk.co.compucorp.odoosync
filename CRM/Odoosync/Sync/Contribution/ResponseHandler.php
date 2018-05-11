<?php

/**
 * Updates contribution sync information when sync successful/error
 */
class CRM_Odoosync_Sync_Contribution_ResponseHandler {

  /**
   * Updates contribution sync information when synchronization was successful
   *
   * @param $contributionId
   * @param $creditNoteNumber
   * @param $invoiceNumber
   * @param $timestamp
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function handleSuccess($contributionId, $creditNoteNumber, $invoiceNumber, $timestamp) {
    $syncStatusId = CRM_Odoosync_Common_OptionValue::getOptionValueID('odoo_sync_status', 'synced');
    $mysqlDate = $this->getMysqlDate($timestamp);

    $paramQuery = [
      1 => [$contributionId, 'Integer'],
      2 => [$syncStatusId , 'String'],
      3 => [$mysqlDate , 'String']
    ];

    $query = "UPDATE odoo_invoice_sync_information AS sync_info SET ";

    if (!is_null($creditNoteNumber)) {
      $query .= "sync_info.odoo_credit_note_number = %5, ";
      $paramQuery[5] = [$creditNoteNumber , 'String'];
    }
    else {
      $query .= "sync_info.odoo_credit_note_number = NULL, ";
    }

    if (!is_null($invoiceNumber)) {
      $query .= "sync_info.odoo_invoice_number = %4, ";
      $paramQuery[4] = [$invoiceNumber , 'String'];
    }
    else {
      $query .= "sync_info.odoo_invoice_number = NULL, ";
    }

    $query .= "
      sync_info.last_successful_sync_date = %3,
      sync_info.sync_status = %2,
      sync_info.action_to_sync = NULL,
      sync_info.action_date = NULL,
      sync_info.last_retry = NULL,
      sync_info.retry_count = 0,
      sync_info.error_log = NULL
      WHERE entity_id = %1 
    ";

    CRM_Core_DAO::executeQuery($query, $paramQuery);
  }

  /**
   * Updates contribution sync information when synchronization was failed
   *
   * @param string $errorMessage
   * @param int $retryThreshold
   * @param $contributionId
   *
   * @param $timestamp
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public function handleError($errorMessage, $retryThreshold, $contributionId, $timestamp) {
    $retryCount = $this->getRetryCount($contributionId);
    $newRetryCount = $retryCount + 1;
    $isReachedRetryThreshold = ($newRetryCount >= $retryThreshold);
    $mysqlDate = $this->getMysqlDate($timestamp);

    $queryParam = [
      1 => [$contributionId, 'Integer'],
      2 => [(int) $newRetryCount , 'Integer'],
      3 => [$errorMessage , 'String'],
      4 => [$mysqlDate , 'String'],
    ];

    if ($isReachedRetryThreshold) {
      $syncFailedStatusId = CRM_Odoosync_Common_OptionValue::getOptionValueID('odoo_sync_status', 'sync_failed');
      $query = "
        UPDATE odoo_invoice_sync_information AS sync_info
        SET
          sync_info.last_retry = %4,
          sync_info.error_log = %3,
          sync_info.retry_count = %2,
          sync_info.sync_status = %5
        WHERE entity_id = %1
      ";
      $queryParam[5] = [$syncFailedStatusId , 'String'];
    }
    else {
      $query = "
        UPDATE odoo_invoice_sync_information AS sync_info
        SET
          sync_info.last_retry = %4,
          sync_info.error_log = %3,
          sync_info.retry_count = %2
        WHERE sync_info.entity_id = %1
      ";
    }

    CRM_Core_DAO::executeQuery($query, $queryParam);

    return $isReachedRetryThreshold;
  }

  /**
   * Gets contribution's retry count
   *
   * @param int $contributionId
   *
   * @return int
   */
  private function getRetryCount($contributionId) {
    $query = "SELECT retry_count FROM odoo_invoice_sync_information WHERE entity_id = %1 LIMIT 1";
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$contributionId, 'Integer']]);

    while ($dao->fetch()) {
      return (int) $dao->retry_count;
    }

    return 0;
  }

  /**
   * Validates timestamp
   *
   * @param $timestamp
   *
   * @return string
   */
  private function getMysqlDate($timestamp) {
    if (empty($timestamp)) {
      return (new DateTime())->format(CRM_Odoosync_Common_Date::MYSQL_FORMAT_DATE);
    }

    return CRM_Odoosync_Common_Date::convertTimestampToDate($timestamp);
  }

}
