<?php

class CRM_Odoosync_Hook_Post_Contact_Entity extends CRM_Odoosync_Hook_Post_Contact_Base {

  /**
   * Updates contact sync information
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function update() {
    $syncInformation = new CRM_Odoosync_Contact_SyncInformationUpdater(
      $this->objectRef->contact_id,
      'awaiting_sync',
      'update',
      (new DateTime())->format('Y-m-d H:i:s')
    );
    $syncInformation->updateSyncInfo();
  }

}