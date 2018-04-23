<?php

/**
 * Abstraction class provides skeleton for calls to Odoo API, and provides log process
 */
abstract class CRM_Odoosync_Sync_BaseHandler {

  /**
   * Sync setting
   *
   * @var array
   */
  protected $setting = [];

  /**
   * True if debug mode is enable, False otherwise
   *
   * @var bool
   */
  private $isDebugMode = FALSE;

  /**
   * Stores the log messages of the sync
   *
   * @var array
   */
  private $log = [];

  /**
   * Schedule job log
   *
   * @var array
   */
  private $jobLog = '';

  /**
   * True if there is an error preventing the sync from running, False otherwise
   *
   * @var bool
   */
  protected $isError = FALSE;

  /**
   * CRM_Odoosync_Sync_Handler constructor.
   *
   * @param array $params
   */
  public function __construct($params) {
    $this->isDebugMode = (boolean) $params['debug'];
    $this->setSettings();
  }

  /**
   * Runs the odoo-civicrm sync
   *
   * @return array
   * @throws \Exception
   */
  public function run() {
    if ($this->isError) {
      return $this->getDebuggingData();
    }

    return $this->startSync();
  }

  /**
   * Starts sync
   */
  abstract protected function startSync();

  /**
   * Returns log messages if debug is enabled
   *
   * @return array
   */
  protected function getDebuggingData() {
    $data = [];
    $data['values'] = $this->jobLog;
    $data['is_error'] = 0;

    if ($this->isDebugMode) {
      $data['debugLog'] = $this->log;
    }

    return $data;
  }

  /**
   * Sets $setting
   */
  private function setSettings() {
    try {
      $setting = CRM_Odoosync_Setting::getInstance();
      $this->setting = $setting->retrieve();
    }
    catch (Exception $e) {
      $this->setting = [];
      $this->isError = TRUE;
      $this->setLog($e->getMessage());
    }
  }

  /**
   * Sets to Schedule log
   *
   * @param string $jobLog
   */
  protected function setJobLog($jobLog) {
    $this->jobLog .= $jobLog . '<br/>';
  }

  /**
   * Sets $log
   *
   * @param mixed $log
   */
  protected function setLog($log) {
    $this->log[] = $log;
  }

}
