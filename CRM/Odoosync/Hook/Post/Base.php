<?php

/**
 * Abstraction class provides skeleton for post processes
 */
abstract class CRM_Odoosync_Hook_Post_Base {

  /**
   * Operation being performed with CiviCRM object
   *
   * @var object
   */
  protected $operation;

  /**
   * Object name
   *
   * @var object
   */
  protected $objectName;

  /**
   * The unique identifier for the object
   *
   * @var object
   */
  protected $objectId;

  /**
   * The reference to the object if available
   *
   * @var object
   */
  protected $objectRef;

  /**
   * CRM_Odoosync_Hook_Post_Base constructor.
   *
   * @param string $op
   * @param string $objectName
   * @param int $objectId
   * @param object $objectRef
   */
  public function __construct($op, $objectName, $objectId, &$objectRef) {
    $this->operation = $op;
    $this->objectName = $objectName;
    $this->objectId = $objectId;
    $this->objectRef = $objectRef;
  }

  abstract public function process();

  /**
   * Checks if is contribution has "Synced" status
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
