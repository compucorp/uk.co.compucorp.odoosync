<?php

/**
 * Login in Odoo API and gets Odoo user id
 */
class CRM_Odoosync_Sync_Request_Auth {

  /**
   * Instance for singleton
   *
   * @var object
   */
  private static $instance;

  /**
   * Setting for Odoo sync
   *
   * @var array
   */
  private $setting;

  /**
   * Odoo userId
   *
   * @var int
   */
  public $odooUserId = FALSE;

  /**
   * Odoo login url
   *
   * @var string
   */
  const ODOO_API_ENDPOINT = '/xmlrpc/2/common';

  /**
   * CRM_Odoosync_Sync_Request_Auth constructor.
   *
   * @throws \Exception
   */
  private function __construct() {
    $syncSetting = CRM_Odoosync_Setting::getInstance();
    $this->setting = $syncSetting->retrieve();
    $this->auth();
  }

  /**
   * Gets singleton instance
   *
   * @return \CRM_Odoosync_Sync_Request_Auth|object
   */
  public static function getInstance() {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Logs in on Odoo API
   * Gets Odoo user id
   */
  private function auth() {
    $url = $this->setting['odoosync_odoo_instance_url'] . self::ODOO_API_ENDPOINT;
    $xml = CRM_Odoosync_Sync_Request_XmlGenerator::generateLoginXml(
      $this->setting['odoosync_database_name'],
      $this->setting['odoosync_username'],
      $this->setting['odoosync_password'],
      'authenticate'
    );

    $responseXml = (new CRM_Odoosync_Sync_Request_Curl)->sendXml($url, $xml);
    $response = CRM_Odoosync_Sync_Request_XmlGenerator::xmlToObject($responseXml);

    if (!$response || (!isset($response->params->param->value->int))) {
      return;
    }

    $this->odooUserId = (string) $response->params->param->value->int;
  }

  private function __clone() {}

  private function __wakeup() {}

}
