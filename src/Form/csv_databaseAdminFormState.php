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

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
//Drupal\Core\Form\FormStateInterface $form_state
class csv_databaseAdminFormState  extends ConfigFormBase {
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ncaoc_pending_cases.settings');
   // dpm($config, 'confg');
      $form['hello_thing'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('thing'),
        '#default_value' => $config->get('things'),
    );
    $form['dropdown_fields'] = array(
        '#type' => 'fieldset',
        '#title' => 'Select Dropdown Fields',   
    );  
    $fields = unserialize(\Drupal::state()->get('csv_database_query_fields'));
    foreach($fields as $field) {
        $form['dropdown_fields'][$field] = array(
        '#type' => 'checkbox',
        '#title' => $field,    
        );     
    }
 
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    );

    // By default, render the form using theme_system_config_form().
    $form['#theme'] = 'system_config_form';

    return parent::buildForm($form, $form_state);
  }

 /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
 //   $this->config('ncaoc_pending_cases.settings')
 //       ->set('things', $form_state->getValue('hello_thing'))
 //       ->save();
    dpm($form_state, 'form array');
    parent::submitForm($form, $form_state);
  }
}