<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @file
 * Contains \Drupal\csv_database_query\Form\csv_databaseAdminForm.
 */

namespace Drupal\csv_database_query\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\csv_database_query\Controller;
//Drupal\Core\Form\FormStateInterface $form_state
class csv_databaseAdminForm  extends FormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'csv_database_query_admin_form';
    }
   /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
        'csv_database_query.settings',
    ];
  }
   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $table_name = null) {
    $config = $this->config('csv_database_query.settings');
    $form['table_name'] = array(
        '#type' => 'hidden',
        '#default_value' => $table_name,
    );

    $form['fields_fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => 'Select Dropdown Fields',   
    );
    $fields = unserialize(\Drupal::state()->get('csv_database_fields_table_'.$table_name));
    // create array of each field in the database
    //  if the name exists in both set the checkbox of the field
    //  if it exists in the DB but not the list then delete it from the DB
    //  if it exists only in the list than do nothing
  
   // TODO need to call function in Controller 
    $db = \Drupal::database();
    $query = $db->select('csv_database_fields_table_'.$table_name, 'fields')
                ->fields('fields', array('name', 'display_type', 'column_heading'));
  //  $query->orderby('name', "ASC");
    $db_names = $query->execute()->fetchall();
    $form['fields_fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => 'Select how each field will be used',
        '#attributes' => array(
            'class' => array('container-inline'),    
        ),
    );
    foreach($fields as $index => $field) {
        // look up each field in fields table
  //      $name = preg_replace('/[^A-Za-z0-9_]"/', "", $field_name);  //*****?????

        $form['fields_fieldset'][$field] = array(
            '#type' => 'radios',
            '#options' => array('hide' => $this->t('HIDE'), 'show' => $this->t('DISPLAY'), 'filter' => $this->t('USE AS FILTER'), 'select' => $this->t('DISPLAY as DROPDOWN')),
            '#title' => $field.'<br/>',
            '#parents' => array('fields_fieldset', $field, 'display'), 
            '#default_value' => 'hide',
    //        '#attributes' => array(
    //            'class' => array('ncaoc_cases_admin_radios'),    
    //        ),
            '#prefix' => '<div class="ncaoc_cases_admin_radios">',
            '#suffix' => '</div>',
        );
        $form['fields_fieldset'][$field]['column_heading'] = array(
            '#type' => 'textfield',
            '#parents' => array('fields_fieldset', $field, 'column_heading'), 
            '#default_value' => ucwords(strtolower(str_replace("_", " ", $field))), 
            '#size' => '25',
            '#attributes' => array(
                'class' => array('ncaoc_cases_admin_heading'),    
            ),
        );
        $form['fields_fieldset'][$field]['display_order'] = array(
            '#type' => 'hidden',
            '#parents' => array('fields_fieldset', $field, 'display_order'), 
            '#value' => $index, 
            '#size' => '25',
        );
        foreach ($db_names as $db_name) {
            
            if ($field == $db_name->name) {
                $form['fields_fieldset'][$field]['#default_value'] = $db_name->display_type;
                $form['fields_fieldset'][$field]['column_heading']['#default_value'] = $db_name->column_heading;
                break;
            }
               $form['fields_fieldset'][$field]['#default_value'] = 'hide';
               
        }
    }

 //   foreach ($db_names as $db_name) {
 //       dpm($fields, $db_name->name);
 //       dpm($field, 'field');
 //       if (in_array($db_name->name, $fields)) {
 //           $form['fields_fieldset'][$field]["#attributes"]['checked'] = TRUE;
 //       }
 //   }
    
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    );

    // By default, render the form using theme_system_config_form().
    $form['#theme'] = 'system_config_form';
    return $form;
  }
public function validateForm(array &$form, FormStateInterface $form_state) {
    $fields = $form_state->getValue('fields_fieldset');
    $no_show_fields = 1;
    foreach($fields as $field) {
        if ($field['display'] == 'show') {
            $no_show_fields = 0;
        }
    }
    if ($no_show_fields) {
        drupal_set_message('At least one field must be set as DISPLAY', 'error');
    }
}

 /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // remove all records from csv_database_query_fields table
    $fields = $form_state->getValue('fields_fieldset');
      $db_table = 'csv_database_query_table_'.$form_state->getValue('table_name');
      $fields_table = 'csv_database_fields_table_'.$form_state->getValue('table_name');
    foreach($fields as $field_name => $field_info) {
        $this->build_field_select_table($db_table, $fields_table, $field_name, $field_info);
    }
  }
  private function build_field_select_table($db_table, $fields_table, $field_name, $field_info) {
   // read record from DB, if it does not exist, perform insert
   // if it exists check of $display equals t  

      $field_array = array(
        'column_heading' => $field_info['column_heading'],  
        'display_type' => $field_info['display'],
        'display_order' => $field_info['display_order'],
    );

      $db = \Drupal::database();
      $query = $db->select($fields_table, 'fields')
        ->fields('fields')      
        ->condition('name', $field_name, '=');

      $result = $query->execute()->fetchall();
      $changed = TRUE;
      if (empty($result)) {
          $query = $db->insert($fields_table);
          $field_array['name'] = $field_name;
      } else {
  
          $query = $db->update($fields_table)
            ->condition('name', $field_name, '=');
          if ($result[0]->display_type == $field_info['display'])   {
              $changed = FALSE; 
          }
      }
  //  if ($name == 'NINETY_DAY_FAILURE_DATE') {
  //      $field_array['display_type'] = 'hide';
  //      $changed = FALSE;
  //  }

    if ($changed) {
        $field_array['options'] = NULL;
        if ($field_info['display'] == 'select') {
            $record_count = $this::create_table_index($field_name);
            if ($record_count < 200) {
              $field_array['options'] = serialize(\Drupal\csv_database_query\Controller\csv_databaseController::findUniqueValues($db_table, $field_name));
            } else {
                 $field_array['options'] = NULL;
                 $field_array['display_type'] = 'filter';
                 drupal_set_message($field_name.' has too many distinct entries, '.$record_count. ', to be eligible as DROPDOWN');
            }  
        }
    }
      $query->fields($field_array)->execute(); 
      // build DB with primary key as read 
  }
  
  private function create_table_index($field) {
    $table = "csv_database_query_table";
    $db = \Drupal::database();
    
    
    $query = $db->select($table, 'pending');
    $query->fields('pending', array($field));
    $query->distinct();
    $record_count = $query->countQuery()->execute()->fetchField();
    //dpm($record_count, 'count');
    if ($record_count < 200) {
        $schema = \Drupal\Core\Database\Database::getConnection()->schema();
        if (!$schema->indexExists($table, 'index_'.$field)) { 
            $spec['fields'][$field] = array(              
                'description' => '',
                'type' => 'text',
            );
            $fields = array($field);
            $schema->addIndex($table, 'index_'.$field, $fields, $spec);
        }
    } 
 return $record_count;
  }
}