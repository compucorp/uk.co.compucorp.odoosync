<?php

/**
 * Handles syncing contribution data to odoo
 */
class CRM_Odoosync_Sync_Contribution extends CRM_Odoosync_Sync_BaseHandler {

  /**
   * The contribution ID to sync
   *
   * @var int
   */
  private $syncContributionId;

  /**
   * Starts contribution Odoo sync
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function startSync() {
    $this->setLog(ts('Start Contribution Syncing ...'));
    $this->setJobLog(ts('Start Contribution Syncing ...'));

    $pendingContribution = new CRM_Odoosync_Sync_Contribution_PendingContribution();
    $contributionIdList = $pendingContribution->getIds();

    if (empty($contributionIdList)) {
      $this->setJobLog(ts('All Contributions are synced'));
      $this->setLog(ts('All Contributions are synced'));
      return $this->getDebuggingData();
    }

    foreach ($contributionIdList as $contributionId) {
      $this->syncContributionId = $contributionId;
      $this->syncContribution();
    }

    return $this->getDebuggingData();
  }

  /**
   * Syncs single contribution
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function syncContribution() {
    $sendData = (new CRM_Odoosync_Sync_Contribution_InformationGenerator($this->syncContributionId))->generate();
    $this->setLog(ts("Prepare contribution(id = %1) to sync", [1 => $this->syncContributionId]));
    $this->setLog(ts("Contribution data:"));
    $this->setLog($sendData);
    $syncResponse = (new CRM_Odoosync_Sync_Request_Contribution())->sync($sendData);
    $this->handleResponse($syncResponse);
  }

  /**
   * Handles Odoo API response and updates contribution sync information
   *
   * @param $syncResponse
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function handleResponse($syncResponse) {
    $this->setLog(ts('Odoo response:'));
    $this->setLog($syncResponse);

    if ($syncResponse['is_error'] == 0) {
      $this->handleSuccessResponse(
        $syncResponse['creditnote_number'],
        $syncResponse['invoice_number'],
        $syncResponse['timestamp']
      );
    }
    else {
      $this->handleErrorResponse($syncResponse['error_message'], $syncResponse['timestamp']);
    }

    $this->setLog(ts('End sync Contribution.'));
  }

  /**
   * Handles success response
   *
   * @param $creditNoteNumber
   * @param $invoiceNumber
   * @param $timestamp
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function handleSuccessResponse($creditNoteNumber, $invoiceNumber, $timestamp) {
    $this->setJobLog(ts('Sync with success. Contribution id = %1.', [1 => $this->syncContributionId]));
    $responseHandler = new CRM_Odoosync_Sync_Contribution_ResponseHandler();
    $responseHandler->handleSuccess($this->syncContributionId, $creditNoteNumber, $invoiceNumber, $timestamp);
    $this->setLog(ts('Successful sync. Contribution data updated.'));
  }

  /**
   * Handles error response
   *
   * @param string $errorMessage
   * @param $timestamp
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function handleErrorResponse($errorMessage, $timestamp) {
    $this->setJobLog(ts('Sync with error. Contribution id = %1.', [1 => $this->syncContributionId]));

    $responseHandler = new CRM_Odoosync_Sync_Contribution_ResponseHandler();
    $isReachedRetryThreshold = $responseHandler->handleError(
      $errorMessage,
      $this->setting['odoosync_retry_threshold'],
      $this->syncContributionId,
      $timestamp
    );
    $this->setLog(ts('Sync with error. Contribution data updated.'));
    $this->setLog($errorMessage);

    if ($isReachedRetryThreshold) {
      $this->setLog(ts("Reached retry threshold counter. 'Sync Status' marked as 'Sync failed. Sending errors to emails.'"));
      $errorMail = new CRM_Odoosync_Mail_Error($this->syncContributionId, 'Contribution', $errorMessage);
      $errorMail->sendToRecipients();
    }
  }

}
