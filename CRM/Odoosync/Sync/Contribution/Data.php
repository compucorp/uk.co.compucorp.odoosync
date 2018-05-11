<?php

/**
 * Abstraction class provides skeleton for each part of contribution information
 */
abstract class CRM_Odoosync_Sync_Contribution_Data {

  /**
   * Sync contribution id
   *
   * @var int
   */
  protected $contributionId;

  /**
   * @param int $contributionId
   */
  public function __construct($contributionId) {
    $this->contributionId = $contributionId;
  }

  /**
   * Returns contribution data
   *
   * @return mixed
   */
  abstract public function retrieve();

}
