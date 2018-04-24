<?php

/**
 * Abstraction class provides skeleton for each part of contact information
 */
abstract class CRM_Odoosync_Sync_Contact_Data {

  /**
   * Sync contact id
   *
   * @var int
   */
  protected $contactId;

  /**
   * @param int $contactId
   */
  public function __construct($contactId) {
    $this->contactId = $contactId;
  }

  /**
   * Returns contact data
   *
   * @return mixed
   */
  abstract public function retrieve();

}
