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

    $batchSize = (int) $this->setting['odoosync_batch_size'];
    $pendingContactsFetcher = new CRM_Odoosync_Sync_Contact_PendingContactsFetcher();

    for ($i = 0; $i < $batchSize; $i++) {
      $this->syncContactId = $pendingContactsFetcher->getPendingContact();

      if (empty($this->syncContactId)) {
        $this->setJobLog(ts('All contact is sync'));
        $this->setLog(ts('All contact is sync'));
        return $this->getDebuggingData();
      }

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
   */
  private function handleResponse($syncResponse) {
    $this->setLog(ts('Odoo response:'));
    $this->setLog($syncResponse);

    //TODO: Handling $syncResponse in COS-17
    if ($syncResponse['is_error'] != 1) {
      $this->setJobLog(
        ts('Sync with success. Contact id = %1. Partner id = %2.',
          [
            1 => $this->syncContactId,
            2 => $syncResponse['partner_id'],
          ]
        )
      );
    }
    else {
      $this->setJobLog(ts('Sync with error. Contact id = %1.', [1 => $this->syncContactId]));
    }

    $this->setLog(ts('End sync contact.'));
  }

}
