<?php

use CRM_Odoosync_ExtensionUtil as E;

/**
 * Odoo Civicrm Sync Configuration form controller
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Odoosync_Form_Configurations extends CRM_Core_Form {

  /**
   * Contains array of fields, which config Odoo Civicrm Sync Expansion
   *
   * @var string[]
   */
  private $settingFields = [];

  /**
   * Builds the form object.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function buildQuickForm() {
    $configElements = [];
    CRM_Utils_System::setTitle(E::ts('CiviCRM Odoo Sync Configuration'));

    $settingFields  = $this->getSettingFields();

    foreach ($settingFields as $name => $config) {
      $this->add(
        $config['html_type'],
        $name,
        E::ts($config['title']),
        CRM_Utils_Array::value('html_attributes', $config, []),
        $config['is_required'],
        CRM_Utils_Array::value('extra_data', $config, [])
      );

      $configElements[] = $name;
    }

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
    ]);

    $this->assign('configElements', $configElements);;
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function postProcess() {
    $this->controller->setDestination(
      CRM_Utils_System::url(
        'civicrm/admin/odoosync/configuration',
        http_build_query(['reset' => 1])
      )
    );

    $settingFields = $this->getSettingFields();
    $submittedValues = $this->exportValues();
    $valuesToSave = array_intersect_key($submittedValues, $settingFields);

    //if empty submitted value, set default data
    foreach ($settingFields as $key => $val) {
      $isEmptySubmittedValue = !array_key_exists($val['name'], $valuesToSave) || empty($valuesToSave[$val['name']]);
      if ($isEmptySubmittedValue && !empty($val['default'])) {
        $valuesToSave[$val['name']] = $val['default'];
      }
    }

    civicrm_api3('setting', 'create', $valuesToSave);
  }

  /**
   * Gets the configurations allowed to be set on this form.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function getSettingFields() {
    if (!empty($this->settingFields)) {
      return $this->settingFields;
    }

    $this->settingFields = self::getSettingValues();

    return $this->settingFields;
  }

  /**
   * Gets setting values used by the extension
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function getSettingValues() {
    return civicrm_api3('setting', 'getfields', [
      'filters' => [ 'group' => 'odoosync'],
    ])['values'];
  }

  /**
   * Sets defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   * @throws \CiviCRM_API3_Exception
   */
  public function setDefaultValues() {
    $defaults = [];
    $domainID = CRM_Core_Config::domainID();
    $settingFields = $this->getSettingFields();

    $currentValues = civicrm_api3('setting', 'get',
      ['return' => array_keys($settingFields)]
    );

    //sets fields values from database
    foreach ($currentValues['values'][$domainID] as $name => $value) {
      $defaults[$name] = $value;
    }

    $defaults = $this->setDefaultsToEmptyFields($defaults);

    return $defaults;
  }

  /**
   * Adds local and global form rules.
   *
   * @return void
   * @throws \HTML_QuickForm_Error
   */
  public function addRules() {
    $this->addFormRule(array(get_class($this), 'validateRules'));
  }

  /**
   * Custom validate method
   *
   * @param $values
   *
   * @return bool|array
   * @throws \CiviCRM_API3_Exception
   */
  public static function validateRules($values) {
    $settingInputs = self::getSettingValues();

    foreach ($settingInputs as $key => $setting) {
      $rule = CRM_Utils_Array::value('validate', $setting, []);

      if (empty($rule)) {
        continue;
      }

      if ($rule == 'Integer' && !CRM_Utils_Rule::integer($values[$setting['name']])) {
        $errors[$setting['name']] = E::ts("Field must be Integer.");
        continue;
      }

      if ($rule == 'Email') {
        $emails = explode(',', $values[$setting['name']]);
        $emailErrorIndex = [];
        foreach ($emails as $numberEmail => $email) {
          if (!CRM_Utils_Rule::email($email)) {
            $emailErrorIndex[] = $numberEmail + 1;
          }
        }
        if (!empty($emailErrorIndex)) {
          $errors[$setting['name']] = E::ts("Email(№ %1) is not valid.", [1 => implode(',', $emailErrorIndex)]);
        }
        continue;
      }
    }

    return empty($errors) ? TRUE : $errors;
  }


  /**
   * If value of fields is empty, sets default value from setting
   *
   * @param array $defaults
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function setDefaultsToEmptyFields($defaults) {
    $settingFields = $this->getSettingFields();

    foreach ($settingFields as $field) {
      $fieldName = $field['name'];
      if (empty($defaults[$fieldName]) && !empty($field['default'])) {
        $defaults[$fieldName] = $field['default'];
      }
    }

    return $defaults;
  }

}
