<?php

class CRM_Odoosync_Hook_Post_Contact_SubEntity extends CRM_Odoosync_Hook_Post_Base {

  /**
   * Updates contact sync information
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function process() {
    $syncInformation = new CRM_Odoosync_Contact_SyncInformationUpdater(
      $this->objectRef->contact_id,
      'awaiting_sync',
      'update',
      (new DateTime())->format('Y-m-d H:i:s')
    );
    $syncInformation->updateSyncInfo();
  }

}
