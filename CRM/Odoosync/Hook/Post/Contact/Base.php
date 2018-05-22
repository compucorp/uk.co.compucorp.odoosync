<?php

/**
 * Abstraction class provides skeleton for contact post processes
 */
abstract class CRM_Odoosync_Hook_Post_Contact_Base extends CRM_Odoosync_Hook_Post_Base {

  /**
   * Updates contact sync information
   */
  public function process() {
    if (CRM_Odoosync_Hook_Post_Contact_Checker::isContactSyncInfoUpdated()) {
      return;
    }

    $this->update();

    CRM_Odoosync_Hook_Post_Contact_Checker::setContactIsUpdated();
  }

  protected abstract function update();

}
