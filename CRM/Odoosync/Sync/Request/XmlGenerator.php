<?php

/**
 * Generates xml for sync to Odoo
 */
class CRM_Odoosync_Sync_Request_XmlGenerator {

  /**
   * Generates xml for login to Odoo
   *
   * @param string $database
   * @param string $username
   * @param string $password
   * @param string $methodName
   *
   * @return string
   */
  public static function generateLoginXml($database, $username, $password, $methodName) {
    $xml = new SimpleXMLElement('<methodCall/>');
    $xml->addChild('methodName', $methodName);
    $params = $xml->addChild('params');

    self::generateTagParam($params, 'string', $database);
    self::generateTagParam($params, 'string', $username);
    self::generateTagParam($params, 'string', $password);
    self::generateTagParam($params, 'struct', '');

    return $xml->asXML();
  }

  /**
   * Generates xml for sync to Odoo
   *
   * @param string $database
   * @param string $password
   * @param string $methodName
   * @param string $odooUserId
   *
   * @return string
   */
  public static function generateSyncOdooXml($database, $password, $methodName, $odooUserId, $sendData) {
    $xml = new SimpleXMLElement('<methodCall/>');
    $xml->addChild('methodName', $methodName);
    $params = $xml->addChild('params');

    self::generateTagParam($params, 'string', $database);
    self::generateTagParam($params, 'int', $odooUserId);
    self::generateTagParam($params, 'string', $password);
    self::generateTagParam($params, 'string', 'res.partner');
    self::generateTagParam($params, 'string', 'civicrm_sync');

    $param = $params->addChild('param');
    $valueElement = $param->addChild('value');
    $arrayElement = $valueElement->addChild('array');
    $dataElement = $arrayElement->addChild('data');
    $valueElement = $dataElement->addChild('value');
    $struct = $valueElement->addChild('struct');

    self::generateTagMember($struct, 'website', 'string', $sendData['website']);
    self::generateTagMember($struct, 'city', 'string', $sendData['city']);
    self::generateTagMember($struct, 'fax', 'string', $sendData['fax']);
    self::generateTagMember($struct, 'display_name', 'string', $sendData['display_name']);
    self::generateTagMember($struct, 'name', 'string', $sendData['name']);
    self::generateTagMember($struct, 'zip', 'string', $sendData['zip']);
    self::generateTagMember($struct, 'title', 'string', $sendData['title']);
    self::generateTagMember($struct, 'mobile', 'string', $sendData['mobile']);
    self::generateTagMember($struct, 'street2', 'string', $sendData['street2']);
    self::generateTagMember($struct, 'country_iso_code', 'string', $sendData['country_iso_code']);
    self::generateTagMember($struct, 'phone', 'string', $sendData['phone']);
    self::generateTagMember($struct, 'street', 'string', $sendData['street']);
    self::generateTagMember($struct, 'customer', 'boolean', $sendData['customer']);
    self::generateTagMember($struct, 'write_date', 'int', $sendData['write_date']);
    self::generateTagMember($struct, 'active', 'boolean', $sendData['active']);
    self::generateTagMember($struct, 'create_date', 'int', $sendData['create_date']);
    self::generateTagMember($struct, 'x_civicrm_id', 'int', $sendData['x_civicrm_id']);
    self::generateTagMember($struct, 'email', 'string', $sendData['email']);
    self::generateTagMember($struct, 'is_company', 'boolean', $sendData['is_company']);

    return $xml->asXML();
  }

  /**
   * Generates xml group tag 'member' and fills it
   *
   * @param \SimpleXMLElement $parentElement
   * @param string $nameValue
   * @param string $type
   * @param string $value
   */
  private static function generateTagMember(&$parentElement, $nameValue, $type, $value) {
    $member = $parentElement->addChild('member');
    $member->addChild('name', $nameValue);
    $valueElement = $member->addChild('value');
    $valueElement->addChild($type, $value);
  }

  /**
   * Generates xml group tag 'param' and fills it
   *
   * @param \SimpleXMLElement $parentElement
   * @param string $type
   * @param string $value
   */
  private static function generateTagParam(&$parentElement, $type, $value) {
    $param = $parentElement->addChild('param');
    $valueElement = $param->addChild('value');
    $valueElement->addChild($type, $value);
  }

  /**
   * Transforms XML string to object
   *
   * @param string $xmlString
   *
   * @return bool|\SimpleXMLElement
   */
  public static function xmlToObject($xmlString) {
    $parsedXml = simplexml_load_string($xmlString);

    if ($parsedXml === FALSE) {
      return FALSE;
    }

    return $parsedXml;
  }

}
