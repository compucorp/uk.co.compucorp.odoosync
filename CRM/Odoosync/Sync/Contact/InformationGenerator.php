<?php

class CRM_Odoosync_Sync_Contact_InformationGenerator {

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
  private $contactData;

  /**
   * Contact data
   *
   * @var array
   */
  private $fieldsToGenerate = [
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

  public function __construct($contactId) {
    $this->contactId = $contactId;
  }

  /**
   * Prepares contact data for sync
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function generate() {
    $this->contactData = civicrm_api3('Contact', 'getsingle', [
      'id' => $this->contactId,
    ]);

    $this->generateNameFields();
    $this->generateAddressFields();
    $this->generateWebsiteField();
    $this->generatePhoneFields();
    $this->generateEmailFields();
    $this->generateDateFields();
    $this->generateOdooRelatedFields();

    return $this->fieldsToGenerate;
  }

  /**
   * Generates name fields
   */
  private function generateNameFields() {
    $displayName = $this->getDisplayName($this->contactData);
    $this->fieldsToGenerate['display_name'] = $displayName;
    $this->fieldsToGenerate['name'] = $displayName;
  }

  /**
   * Generates address fields
   */
  private function generateAddressFields() {
    $contactDataAddress = new CRM_Odoosync_Sync_Contact_Data_Address($this->contactId);
    $addressData = $contactDataAddress->retrieve();
    $this->fieldsToGenerate['street'] = $addressData['streetAddress'];
    $this->fieldsToGenerate['street2'] = $addressData['supplementalAddress'];
    $this->fieldsToGenerate['city'] = $addressData['city'];
    $this->fieldsToGenerate['zip'] = $addressData['postalCode'];
    $this->fieldsToGenerate['country_iso_code'] = $addressData['countryIsoCode'];
  }

  /**
   * Generates website field
   */
  private function generateWebsiteField() {
    $contactDataWebsite = new CRM_Odoosync_Sync_Contact_Data_Website($this->contactId);
    $this->fieldsToGenerate['website'] = $contactDataWebsite->retrieve();
  }

  /**
   * Generates phone fields
   */
  private function generatePhoneFields() {
    $contactDataPhone = new CRM_Odoosync_Sync_Contact_Data_Phone($this->contactId);
    $phoneData = $contactDataPhone->retrieve();
    $this->fieldsToGenerate['phone'] = $phoneData['phoneNumber'];
    $this->fieldsToGenerate['mobile'] = $phoneData['mobileNumber'];
    $this->fieldsToGenerate['fax'] = $phoneData['faxNumber'];
  }

  /**
   * Generates email fields
   */
  private function generateEmailFields() {
    $contactDataEmail = new CRM_Odoosync_Sync_Contact_Data_Email($this->contactId);
    $this->fieldsToGenerate['email'] = $contactDataEmail->retrieve();
  }

  /**
   * Generates date fields
   */
  private function generateDateFields() {
    $contactDate = CRM_Contact_BAO_Contact::getTimestamps($this->contactId);
    if (!empty($contactDate['modified_date'])) {
      $this->fieldsToGenerate['write_date'] = CRM_Odoosync_Common_Date::convertDateToTimestamp($contactDate['modified_date']);
    }
    if (!empty($contactDate['created_date'])) {
      $this->fieldsToGenerate['create_date'] = CRM_Odoosync_Common_Date::convertDateToTimestamp($contactDate['created_date']);
    }
  }

  /**
   * Generates Odoo related fields
   */
  private function generateOdooRelatedFields() {
    $this->fieldsToGenerate['is_company'] = ($this->contactData['contact_type'] == 'Organization') ? 1 : 0;
    $this->fieldsToGenerate['active'] = ($this->contactData['contact_is_deleted'] == '1') ? 0 : 1;
    $this->fieldsToGenerate['x_civicrm_id'] = $this->contactData['contact_id'];
    $this->fieldsToGenerate['title'] = $this->fixPrefixName($this->contactData['individual_prefix']);
    $this->fieldsToGenerate['customer'] = '1';
  }

  /**
   * Fixes contact prefix name
   * In Odoo prefixes "Ms." not exist, but has equal "Miss"
   *
   * @param string $prefixName
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
   * Prepares contact's display name
   *
   * @param array $contact
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
