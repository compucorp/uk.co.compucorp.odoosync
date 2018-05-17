<?php

class CRM_Odoosync_Sync_BatchSize {

  /**
   * Current batch size
   *
   * @var int|NULL
   */
  private static $currentBatchSize = NULL;

  /**
   * Gets current batch size
   *
   * @return null
   */
  public static function getCurrentBatchSize() {
    if (is_null(self::$currentBatchSize)) {
      self::setBatchSize();
    }

    return self::$currentBatchSize;
  }


  /**
   * Decreases number of available lines in current batch
   *
   * @param int $usedSize
   */
  public static function setUsedSize($usedSize) {
    if (is_null(self::$currentBatchSize)) {
      return;
    }

    self::$currentBatchSize -= (int) $usedSize;
  }

  /**
   * Sets batch size from settings
   */
  private static function setBatchSize() {
    $syncSetting = CRM_Odoosync_Setting::getInstance()->retrieve();
    self::$currentBatchSize = (int) $syncSetting['odoosync_batch_size'];
  }

}
