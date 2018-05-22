<?php

/**
 * Check if contact 'sync info' is already update
 */
class CRM_Odoosync_Hook_Post_Contact_Checker {

  /**
   * Is contact 'sync info' already updated
   *
   * @var bool
   */
  private static $isSyncInfoUpdated = FALSE;

  /**
   * @return bool
   */
  public static function isContactSyncInfoUpdated() {
    return self::$isSyncInfoUpdated;
  }

  /**
   * Sets true for isSyncInfoUpdated
   */
  public static function setContactIsUpdated() {
    self::$isSyncInfoUpdated = TRUE;
  }

}
