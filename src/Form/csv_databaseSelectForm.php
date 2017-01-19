<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @file
 * Contains \Drupal\csv_database_query\Form\csv_databaseSelectForm.
 */

namespace Drupal\csv_database_query\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\csv_database_query\Controller;
//Drupal\Core\Form\FormStateInterface $form_state
class csv_databaseSelectForm  extends FormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'csv_database_select_form';
    }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
      $uid = \Drupal::currentUser()->id();
      $query = \Drupal::entityQuery('node')
          ->condition('status', 1)
          ->condition('uid', $uid, '=')
          ->condition('type', 'csv_table', '=');

      $nids = $query->execute();
      $nodes = entity_load_multiple('node', $nids);
      $options = array();
      foreach ($nodes as $node) {
          $table_name = $node->title->value;
          $options[$table_name] = $table_name;
      }


    $form['table_name'] = [
        '#type' => 'select',
        '#title' => $this->t('Select table name'),
        '#options' => $options,
    ];


    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Select table'),
    );

    return $form;
  }
public function validateForm(array &$form, FormStateInterface $form_state) {

}

 /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $parameters['table_name'] = $form_state->getValue('table_name');
    $form_state->setRedirect('csv_database_query.adminFieldsForm', $parameters);
  }

  

}