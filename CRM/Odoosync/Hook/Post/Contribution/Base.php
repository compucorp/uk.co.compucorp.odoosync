<?php

/**
 * Abstraction class provides skeleton for contribution post processes
 */
abstract class CRM_Odoosync_Hook_Post_Contribution_Base extends CRM_Odoosync_Hook_Post_Base {

  /**
   * Checks if is contribution previously been synced to Odoo
   *
   * @param $contributionId
   *
   * @return bool
   */
  protected function isSyncStatusSynced($contributionId) {
    $query = "
      SELECT * FROM odoo_invoice_sync_information AS sync_info 
      WHERE sync_info.entity_id = %1 AND sync_info.last_successful_sync_date IS NOT NULL 
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$contributionId, 'Integer']
    ]);

    return !empty($dao->fetchAll());
  }

}
