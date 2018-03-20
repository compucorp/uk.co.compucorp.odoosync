<?php

/*
 * Metadata for Odoo civicrm sync Configuration
 */
return [
  'odoosync_odoo_instance_url' => [
    'group' => 'odoosync',
    'name' => 'odoosync_odoo_instance_url',
    'title' => 'Odoo Instance URL',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '',
    'is_required' => TRUE,
    'html_attributes' => '',
    'extra_data' => '',
    'section' => 'sync_config',
    'type' => 'String',
    'group_name' => 'Odoo civicrm sync Configuration',
    'validate' => '',
  ],
  'odoosync_database_name' => [
    'group' => 'odoosync',
    'name' => 'odoosync_database_name',
    'title' => 'Odoo Database Name',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '',
    'is_required' => TRUE,
    'html_attributes' => '',
    'extra_data' => '',
    'section' => 'sync_config',
    'type' => 'String',
    'group_name' => 'Odoo civicrm sync Configuration',
    'validate' => '',
  ],
  'odoosync_username' => [
    'group' => 'odoosync',
    'name' => 'odoosync_username',
    'title' => 'Username',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '',
    'is_required' => TRUE,
    'html_attributes' => '',
    'extra_data' => '',
    'section' => 'sync_config',
    'type' => 'String',
    'group_name' => 'Odoo civicrm sync Configuration',
    'validate' => '',
  ],
  'odoosync_password' => [
    'group' => 'odoosync',
    'name' => 'odoosync_password',
    'title' => 'Password',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '',
    'is_required' => TRUE,
    'html_attributes' => '',
    'extra_data' => '',
    'section' => 'sync_config',
    'type' => 'String',
    'group_name' => 'Odoo civicrm sync Configuration',
    'validate' => '',
  ],
  'odoosync_batch_size' => [
    'group' => 'odoosync',
    'name' => 'odoosync_batch_size',
    'title' => 'Batch Size',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '500',
    'is_required' => TRUE,
    'html_attributes' => '',
    'extra_data' => '',
    'section' => 'sync_config',
    'type' => 'String',
    'group_name' => 'Odoo civicrm sync Configuration',
    'validate' => 'Integer',
  ],
  'odoosync_retry_threshold' => [
    'group' => 'odoosync',
    'name' => 'odoosync_retry_threshold',
    'title' => 'Retry Threshold',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '',
    'is_required' => TRUE,
    'html_attributes' => '',
    'extra_data' => '',
    'section' => 'sync_config',
    'type' => 'String',
    'group_name' => 'Odoo civicrm sync Configuration',
    'validate' => 'Integer',
  ],
  'odoosync_error_notice_address' => [
    'group' => 'odoosync',
    'name' => 'odoosync_error_notice_address',
    'title' => 'Error Notice Address',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '',
    'is_required' => TRUE,
    'html_attributes' => '',
    'extra_data' => '',
    'section' => 'sync_config',
    'type' => 'String',
    'group_name' => 'Odoo civicrm sync Configuration',
    'validate' => 'Email',
  ],
];
