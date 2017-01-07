<?php
/*
 * select field and that makes visible the associated query informatioopens SelectTo change this license header, choose License Headers in Project Properties.
 * n To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @file
 * Contains \Drupal\csv_database_query\Form\csv_databaseQueryForm.
 */

namespace Drupal\csv_database_query\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\csv_database_query\Controller;
//Drupal\Core\Form\FormStateInterface $form_state
class csv_databaseQueryForm  extends FormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'csv_database_query_form';
    }
   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('csv_database_query_.settings');
    // TODO: change to use getFieldsList()???
    $db = \Drupal::database();
    $query = $db->select('csv_database_query_fields', 'fields')
        ->fields('fields')
        ->orderby('display_order');
    $db_names = $query->execute()->fetchall();
    $form['query_fieldset'] = array(
        '#type' => 'fieldset',
       '#title' => t('Query Fields'),
//        '#attributes' => array(
//            'class' => array('container-inline'),    
//        ),
    );
    foreach ($db_names as $field) {
        if (!in_array( $field->display_type, array('hide', 'show'))) {
            $form['query_fieldset'][$field->name] = array(
                '#type' => 'fieldset',
                '#title' => t($field->column_heading),
                '#attributes' => array(
                    'class' => array('container-inline'),    
                ),
                '#parents' => array('query_fieldset',$field->name, 'name'),
            );
  //          $form['query_fieldset'][$field->name]['flag'] = array(
  //              '#type' => 'checkbox',
  //              '#title' => t($field->column_heading),
  //              '#suffix' => '<br>',
  //              '#parents' => array('query_fieldset',$field->name, 'flag'),
  //          );
        switch ($field->display_type) {
            case 'show':
            break;
            case 'filter':
                $form['query_fieldset'][$field->name]['operator'] = array(
                    '#type' => 'select',
                    '#title' => $this->t('Condition'),
                    '#parents' => array('query_fieldset',$field->name, 'operator'),
                    '#options' => array(
                        '' => '',
                        '=' => 'EQUAL',
                        '<>' => 'NOT EQUAL',
                        '>' => 'GREATER THAN',
                        '<' => 'LESS THAN',
                        'in' => 'IN',
                    ),
    //        '#suffix' => '</div>',
                );                
                $form['query_fieldset'][$field->name]['value'] = array(
                    '#type' => 'textfield',      
                    '#title' => $this->t('Enter filter value'),
                    '#parents' => array('query_fieldset',$field->name, 'value'),
                    '#size' => 25,
                );
            break;
            case  'select':
                $form['query_fieldset'][$field->name]['value'] = array(
                    '#type' => 'select',      
                    '#title' => $this->t('Select'),
                    '#parents' => array('query_fieldset',$field->name, 'value'),
                );
                $form['query_fieldset'][$field->name]['operator'] = array(
                    '#type' => 'hidden',
                    '#parents' => array('query_fieldset',$field->name, 'operator'),
                    '#value' => '=',
    //        '#suffix' => '</div>',
                );
                $options = array();
                $option_list = unserialize($field->options);
                $options[''] = 'select entry';
                foreach ($option_list as $option_name) {
                    $options[$option_name] = $option_name;
                }
                $form['query_fieldset'][$field->name]['value']['#options'] =  $options;
            break;
        }
    }
       
 //   } 
    } 
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Perform search'),
      '#button_type' => 'primary',
    );
    $form['#attached']['library'][] = 'ncaoc_pending_cases/drupal.pending_cases_query';
    $form['#theme'] = 'system_config_form';
    return $form;
  }

 /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $show_fields = array();
    $query_fields = $form_state->getValue('query_fieldset');
//    foreach($query_fields as $name => $values) {
//        if ($values['flag'] == 1) {
//            $show_fields[] = $name;
//        }
//    }

    $parameters = array();
    $conditions = array();
 //    $parameters['show_fields'] = $show_fields;
    foreach ($query_fields as $name => $values) {
        if (isset($values['value'])) {
            $conditions[$name] = array('value' => $values['value'], 'operator' => $values['operator']);
        }
        
    }
    $parameters['conditions'] = $conditions;
    $form_state->setRedirect('csv_database_query.queryResults', $parameters);
  }
}