<?php
/*
 * select field and that makes visible the associated query informatioopens SelectTo change this license header, choose License Headers in Project Properties.
 * n To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @file
 * Contains \Drupal\csv_database_query\Form\csv_databaseUploadForm.
 */

namespace Drupal\csv_database_query\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\csv_database_query\Controller;
use Drupal\file\Entity;
//use Drupal\Core\Session;
use Drupal\Core\Session\AccountInterface;
use \Drupal\node\Entity\Node;
//use \Drupal\file\Entity\File;

class csv_databaseUploadForm  extends FormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'csv_database_upload_form';
    }
   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  //  $config = $this->config('csv_database_query.settings');

      $form['file'] = array(
          '#type' => 'managed_file',
          '#title' => $this->t('upload file'),
          '#required' => TRUE,
          '#upload_validators'  => array(
              'file_validate_extensions' => array('csv pdf doc docx'),
              'file_validate_size' => array(25600000),
          ),
          '#upload_location' => 'public://csv_files/',
      );

      $form['db_name'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Enter name of Database'),
          '#description' => $this->t('Use underscores instead of spaces'),
      );

      $form['submit'] = [
          '#type' => 'submit',
          '#value' => t('Convert file'),
      ];

    return $form;
  }

 /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      $table_name = $form_state->getValue('db_name');
      $fid = $form_state->getValue('file');
      $file_info = $this::getFileName($fid);
      $uid = \Drupal::currentUser()->id();
      drupal_set_message ('Your file '.$file_info['filename'].$uid.' has been uploaded and will be converted into table '.$table_name.
        ' locationed at '.$file_info['uri']);
   //   will now be converted to Database files '.$user_name);
      $parameters['fid'] = $file_info['fid'];
      //$parameters['file_name'] = $file_name;
      $parameters['table_name'] = $table_name;
      self::writeCSVTableNode($table_name);
      $form_state->setRedirect('csv_database_query.convertFile', $parameters);
  }

    public static function getFileName($fid) {
        $db = \Drupal::database();
        $data = $db->select('file_managed', 'fe')
            ->fields('fe')
            ->orderBy('fe.fid', 'DESC')
            ->range(0, 1)
            ->condition('fe.fid', $fid, '=')
            ->execute();
        $file_info = $data->fetchAssoc();
        return $file_info;
    }

    private static function writeCSVTableNode($table_name) {

// Create file object from remote URL.
      //  $data = file_get_contents('https://www.drupal.org/files/druplicon.small_.png');
      //  $file = file_save_data($data, 'public://druplicon.png', FILE_EXISTS_REPLACE);

// Create node object with attached file.
        $node = Node::create([
            'type'        => 'csv_table',
            'title'       => $table_name,
        ]);
        $node->save();
    }
}