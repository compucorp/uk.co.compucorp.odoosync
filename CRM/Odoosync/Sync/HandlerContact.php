<?php

/**
 * Odoo sync controller, calls to Odoo API, and provides log process
 */
class CRM_Odoosync_Sync_HandlerContact extends CRM_Odoosync_Sync_Handler{

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
    $this->setLog(ts('Start syncing ...'));

    $batchSize = (int) $this->setting['odoosync_batch_size'];
    $syncInformation = new CRM_Odoosync_Sync_Contact_SyncInformation();

    for ($i = 0; $i < $batchSize; $i++) {
      $this->syncContactId = $syncInformation->getFirstNotSyncContactId();

      if (empty($this->syncContactId)) {
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

    //TODO: in COS-17
  }

}
