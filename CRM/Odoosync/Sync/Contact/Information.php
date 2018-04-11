<?php

/**
 * Prepares all contact information for synchronization with Odoo
 */
class CRM_Odoosync_Sync_Contact_Information {

  /**
   * Sync contact id
   *
   * @var int
   */
  private $contactId;

  /**
   * Contact data
   *
   * @var array
   */
  private $contactData = [
    'is_company' => '',
    'x_civicrm_id' => '',
    'display_name' => '',
    'name' => '',
    'title' => '',
    'street' => '',
    'street2' => '',
    'city' => '',
    'zip' => '',
    'country_iso_code' => '',
    'website' => '',
    'phone' => '',
    'mobile' => '',
    'fax' => '',
    'email' => '',
    'active' => '',
    'create_date' => 0,
    'customer' => '',
    'write_date' => 0
  ];

  /**
   * CRM_Odoosync_Utils_ContactData constructor.
   *
   * @param int $contactId
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct($contactId) {
    $this->contactId = $contactId;
    $this->prepareData();
  }

  /**
   * Prepares contact data for sync
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function prepareData() {
    $contact = civicrm_api3('Contact', 'getsingle', [
      'id' => $this->contactId,
    ]);

    //sets display_name
    $displayName = $this->getDisplayName($contact);
    $this->contactData['display_name'] = $displayName;
    $this->contactData['name'] = $displayName;

    //sets street, street2, city, zip, country_iso_code
    $contactDataAddress = new CRM_Odoosync_Sync_Contact_Data_Address($this->contactId);
    $addressData = $contactDataAddress->retrieveData();
    $this->contactData['street'] = $addressData['streetAddress'];
    $this->contactData['street2'] = $addressData['supplementalAddress'];
    $this->contactData['city'] = $addressData['city'];
    $this->contactData['zip'] = $addressData['postalCode'];
    $this->contactData['country_iso_code'] = $addressData['countryIsoCode'];

    //sets website
    $contactDataWebsite = new CRM_Odoosync_Sync_Contact_Data_Website($this->contactId);
    $this->contactData['website'] = $contactDataWebsite->retrieveData();

    //sets phone, mobile, fax
    $contactDataPhone = new CRM_Odoosync_Sync_Contact_Data_Phone($this->contactId);
    $phoneData = $contactDataPhone->retrieveData();
    $this->contactData['phone'] = $phoneData['numberPhone'];
    $this->contactData['mobile'] = $phoneData['numberMobile'];
    $this->contactData['fax'] = $phoneData['numberFax'];

    //sets email
    $contactDataEmail = new CRM_Odoosync_Sync_Contact_Data_Email($this->contactId);
    $this->contactData['email'] = $contactDataEmail->retrieveData();

    //sets modified_date, created_date
    $contactDate = CRM_Contact_BAO_Contact::getTimestamps($this->contactId);
    if (!empty($contactDate['modified_date'])) {
      $this->contactData['write_date'] = $this->convertDateToTimestamp($contactDate['modified_date']);
    }
    if (!empty($contactDate['created_date'])) {
      $this->contactData['create_date'] = $this->convertDateToTimestamp($contactDate['created_date']);
    }

    //sets is_company, active, x_civicrm_id, title, customer
    $this->contactData['is_company'] = ($contact['contact_type'] == 'Organization') ? 1 : 0;
    $this->contactData['active'] = ($contact['contact_is_deleted'] == '1') ? 0 : 1;
    $this->contactData['x_civicrm_id'] = $contact['contact_id'];
    $this->contactData['title'] = $this->fixPrefixName($contact['individual_prefix']);
    $this->contactData['customer'] = '1';
  }

  /**
   * Retrieves contact data for sync
   *
   * @return array
   */
  public function retrieveData() {
    return $this->contactData;
  }

  /**
   * Fixes contact prefix name
   * In Odoo prefixes "Ms." not exist, but has equal "Miss"
   *
   * @param $prefixName
   *
   * @return string
   */
  private function fixPrefixName($prefixName) {
    if (empty($prefixName)) {
      return '';
    }

    if ($prefixName == "Ms.") {
      $prefixName = "Miss";
    }

    return $prefixName;
  }

  /**
   * Converts MySQL date format into timestamp
   *
   * @param $mysqlDate
   *
   * @return int
   */
  public function convertDateToTimestamp($mysqlDate) {
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $mysqlDate);
    return $date->getTimestamp();
  }

  /**
   * Prepares contact's display name
   *
   * @param $contact
   *
   * @return string
   */
  private function getDisplayName($contact) {
    $names = [];

    if (!empty($contact['first_name'])) {
      $names[] = $contact['first_name'];
    }

    if (!empty($contact['middle_name'])) {
      $names[] = $contact['middle_name'];
    }

    if (!empty($contact['last_name'])) {
      $names[] = $contact['last_name'];
    }

    if (!empty($names)) {
      $displayName = implode(' ', $names);
    }
    else {
      $displayName = $contact['display_name'];
    }

    return $displayName;
  }

}
