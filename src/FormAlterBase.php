<?php

namespace Aten\DrupalTools;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class FormAlterBase {

  use StringTranslationTrait;

  /**
   * @var Form build array
   */
  protected $form;

  /**
   * @var FormStateInterface
   */
  protected $formState;

  /**
   * Additional context for widget form
   *
   * @var array
   */
  protected $context;

  /**
   * FormAlterBase constructor.
   *
   * @param $form
   *   Form build array.
   * @param FormStateInterface $form_state
   *   Form $form_state object.
   * @param array $context
   *   Additional context for widget form.
   */
  public function __construct(&$form, FormStateInterface $form_state, array $context) {
    $this->form = &$form;
    $this->formState = $form_state;
    $this->context = $context;
  }

  /**
   * @param ContainerInterface $container
   * @param $form
   * @param FormStateInterface $form_state
   * @param array $context
   * @return static
   */
  public static function createInstance(ContainerInterface $container, &$form, FormStateInterface $form_state, array $context) {
    return new static($form, $form_state, $context);
  }

  /**
   * @param $form
   * @param FormStateInterface $form_state
   * @param array $context
   * @return static
   */
  public static function alter(&$form, FormStateInterface $form_state, $context = []) {
    $instance = static::createInstance(\Drupal::getContainer(), $form, $form_state, $context);
    $instance->doAlter();
    // We could also include the arguments in doAlter to make it easier.
    return $instance;
  }

  /**
   * Implementation form alter code goes here.
   */
  abstract public function doAlter();
}
