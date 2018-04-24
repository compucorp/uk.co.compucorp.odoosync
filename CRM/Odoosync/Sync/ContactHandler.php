<?php

/**
 * Odoo sync controller, calls to Odoo API, and provides log process
 */
class CRM_Odoosync_Sync_ContactHandler extends CRM_Odoosync_Sync_Handler {

  /**
   * Sync contact id
   *
   * @var int
   */
  protected $syncContactId;

  /**
   * Starts contact Odoo sync
   */
  protected function syncStart() {
    $this->setLog(ts('Start contact syncing ...'));
    $this->setJobLog(ts('Start contact syncing ...'));

    $batchSize = (int) $this->setting['odoosync_batch_size'];
    $syncInformation = new CRM_Odoosync_Sync_Contact_SyncInformation();

    for ($i = 0; $i < $batchSize; $i++) {
      $this->syncContactId = $syncInformation->getFirstNotSyncContactId();

      if (empty($this->syncContactId)) {
        $this->setJobLog(ts('All contact is sync'));
        $this->setLog(ts('All contact is sync'));
        return $this->getReturnData();
      }

      $this->syncSingleContact();
    }

    return $this->getReturnData();
  }

  /**
   * Syncs single contact
   */
  private function syncSingleContact() {
    $this->setLog(ts("Prepare contact(id = %1) to sync", [1 => $this->syncContactId]));

    $sendData = (new CRM_Odoosync_Sync_Contact_Information($this->syncContactId))->retrieveData();

    $this->setLog(ts("Contact data:"));
    $this->setLog($sendData);

    $this->callOdooApi($sendData);
  }

  /**
   * Sends contact's prepared data to Odoo API
   *
   * @param array $sendData
   */
  private function callOdooApi($sendData) {
    $this->setLog(ts('Call Odoo Api ...'));

    $login = CRM_Odoosync_Sync_Request_Auth::getInstance();
    if ($login->odooUserId === FALSE) {
      $this->setJobLog(ts('Error. Failed Odoo login.'));
      $this->setLog(ts('Failed Odoo login.'));
      return;
    }

    $syncContact = new CRM_Odoosync_Sync_Request_Contact($login->odooUserId);
    $syncResponse = $syncContact->sync($sendData);
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
