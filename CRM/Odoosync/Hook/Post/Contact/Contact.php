<?php

class CRM_Odoosync_Hook_Post_Contact_Contact extends CRM_Odoosync_Hook_Post_Contact_Base {

  /**
   * Updates contact sync information
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function update() {
    $actionToSync = ($this->operation == 'create') ? 'create' : 'update';
    $syncInformation = new CRM_Odoosync_Contact_SyncInformationUpdater(
      $this->objectRef->id,
      'awaiting_sync',
      $actionToSync,
      (new DateTime())->format('Y-m-d H:i:s')
    );
    $syncInformation->updateSyncInfo();
  }

}