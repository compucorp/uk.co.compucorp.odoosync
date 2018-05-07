<?php

/**
 * Handles syncing contact data to odoo
 */
class CRM_Odoosync_Sync_Contact extends CRM_Odoosync_Sync_BaseHandler {

  /**
   * The contact to be synced Id
   *
   * @var int
   */
  private $syncContactId;

  /**
   * Starts contact Odoo sync
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function startSync() {
    $this->setLog(ts('Start Contacts Syncing ...'));
    $this->setJobLog(ts('Start Contacts Syncing ...'));

    $pendingContacts = new CRM_Odoosync_Sync_Contact_PendingContacts();
    $contactIdList = $pendingContacts->getPendingContacts();

    if (empty($contactIdList)) {
      $this->setJobLog(ts('All contact is sync'));
      $this->setLog(ts('All contact is sync'));
      return $this->getDebuggingData();
    }

    foreach ($contactIdList as $contactId) {
      $this->syncContactId = $contactId;
      $this->syncContact();
    }

    return $this->getDebuggingData();
  }

  /**
   * Syncs single contact
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function syncContact() {
    $sendData = (new CRM_Odoosync_Sync_Contact_InformationGenerator($this->syncContactId))->generate();
    $this->setLog(ts("Prepare contact(id = %1) to sync", [1 => $this->syncContactId]));
    $this->setLog(ts("Contact data:"));
    $this->setLog($sendData);

    $syncResponse = (new CRM_Odoosync_Sync_Request_Contact())->sync($sendData);
    $this->handleResponse($syncResponse);
  }

  /**
   * Handles Odoo API response and updates user sync information
   *
   * @param $syncResponse
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function handleResponse($syncResponse) {
    $this->setLog(ts('Odoo response:'));
    $this->setLog($syncResponse);

    if ($syncResponse['is_error'] == 0) {
      $this->handleSuccessResponse($syncResponse['partner_id']);
    }
    else {
      $this->handleErrorResponse($syncResponse['error_message']);
    }

    $this->setLog(ts('End sync contact.'));
  }

  /**
   * Handles success response
   *
   * @param $partnerId
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function handleSuccessResponse($partnerId) {
    $this->setJobLog(
      ts('Sync with success. Contact id = %1. Partner id = %2.',
        [
          1 => $this->syncContactId,
          2 => $partnerId,
        ]
      )
    );
    $syncInformation = new CRM_Odoosync_Sync_Contact_ResponseHandler();
    $syncInformation->handleSuccess($partnerId, $this->syncContactId);
    $this->setLog(ts('Successful sync. Contact data updated.'));
  }

  /**
   * Handles error response
   *
   * @param $errorMessage
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function handleErrorResponse($errorMessage) {
    $this->setJobLog(ts('Sync with error. Contact id = %1.', [1 => $this->syncContactId]));

    $syncInformation = new CRM_Odoosync_Sync_Contact_ResponseHandler();
    $isReachedRetryThreshold = $syncInformation->handleError(
      $errorMessage,
      $this->setting['odoosync_retry_threshold'],
      $this->syncContactId
    );
    $this->setLog(ts('Sync with error. Contact data updated.'));
    $this->setLog($errorMessage);

    if ($isReachedRetryThreshold) {
      $this->setLog(ts("Reached retry threshold counter. 'Sync Status' marked as 'Sync failed. Sending errors to emails.'"));
      $errorMail = new CRM_Odoosync_Mail_Error($this->syncContactId, 'Contact', $errorMessage);
      $errorMail->sendToRecipients();
    }
  }

}
