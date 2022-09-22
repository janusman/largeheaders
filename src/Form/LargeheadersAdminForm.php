<?php

namespace Drupal\largeheaders\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for settings.
 */
class LargeheadersAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'largeheaders_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['largeheaders.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $long_header_length = $this->config('largeheaders.settings')->get('length_threshold');
    $total_data_threshold = $this->config('largeheaders.settings')->get('total_data_threshold');
    $num_headers_threshold = $this->config('largeheaders.settings')->get('num_headers_threshold');
    $form['length_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum for single header length (bytes)'),
      '#description' => $this->t('Flag response headers as "long" if any are longer than this amount of bytes. Recommend: 5000'),
      '#default_value' => $long_header_length,
    ];
    $form['total_data_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum for total header data length (bytes)'),
      '#description' => $this->t('Flag overall total header data as "long" if the sum amount of data is longer than this amount of bytes. Recommend: 10000'),
      '#default_value' => $total_data_threshold,
    ];
    $form['num_headers_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum for number of individual headers'),
      '#description' => $this->t('Flag when a response has more than this amount of individual headers. Recommend: 30'),
      '#default_value' => $num_headers_threshold,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $long_header_length = $form_state->getValue('length_threshold');
    $total_data_threshold = $form_state->getValue('total_data_threshold');
    $num_headers_threshold = $form_state->getValue('num_headers_threshold');
    $config = $this->config('largeheaders.settings');
    $config->set('length_threshold', $long_header_length)->save();
    $config->set('total_data_threshold', $total_data_threshold)->save();
    $config->set('num_headers_threshold', $num_headers_threshold)->save();
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
