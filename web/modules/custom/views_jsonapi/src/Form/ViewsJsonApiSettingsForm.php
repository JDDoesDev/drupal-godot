<?php

namespace Drupal\views_jsonapi\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Views JSON:API settings.
 */
class ViewsJsonApiSettingsForm extends ConfigFormBase
{

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs a new ViewsJsonApiSettingsForm.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager)
    {
        $this->entityTypeManager = $entity_type_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'views_jsonapi_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['views_jsonapi.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('views_jsonapi.settings');

        $form['general'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('General settings'),
        ];

        $form['general']['include_view_metadata'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Include view metadata in responses'),
            '#description' => $this->t('If checked, view information (ID, display, title) will be included in the JSON:API response metadata.'),
            '#default_value' => $config->get('include_view_metadata') ?? TRUE,
        ];

        $form['general']['preserve_field_names'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Preserve field names'),
            '#description' => $this->t('If checked, field names in the JSON:API response will match the field names used in the view. Otherwise, they will be converted to conform to JSON:API spec (e.g., field_name instead of field-name).'),
            '#default_value' => $config->get('preserve_field_names') ?? FALSE,
        ];

        $form['caching'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Caching settings'),
        ];

        $form['caching']['cache_enabled'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable caching'),
            '#description' => $this->t('If checked, JSON:API view responses will be cached. This may improve performance but could lead to stale data.'),
            '#default_value' => $config->get('cache_enabled') ?? TRUE,
        ];

        $form['caching']['cache_max_age'] = [
            '#type' => 'number',
            '#title' => $this->t('Cache maximum age'),
            '#description' => $this->t('Maximum age (in seconds) for cached JSON:API view responses.'),
            '#default_value' => $config->get('cache_max_age') ?? 3600,
            '#min' => 0,
            '#states' => [
                'visible' => [
                    ':input[name="cache_enabled"]' => ['checked' => TRUE],
                ],
            ],
        ];

        $form['advanced'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Advanced settings'),
            '#collapsible' => TRUE,
            '#collapsed' => TRUE,
        ];

        $form['advanced']['filter_mapping'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable filter mapping'),
            '#description' => $this->t('If checked, you can create custom mappings between JSON:API filter parameters and View exposed filters.'),
            '#default_value' => $config->get('filter_mapping') ?? FALSE,
        ];

        $form['advanced']['include_entity_links'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Include entity links'),
            '#description' => $this->t('If checked, JSON:API responses will include links to related entities.'),
            '#default_value' => $config->get('include_entity_links') ?? TRUE,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->config('views_jsonapi.settings')
            ->set('include_view_metadata', $form_state->getValue('include_view_metadata'))
            ->set('preserve_field_names', $form_state->getValue('preserve_field_names'))
            ->set('cache_enabled', $form_state->getValue('cache_enabled'))
            ->set('cache_max_age', $form_state->getValue('cache_max_age'))
            ->set('filter_mapping', $form_state->getValue('filter_mapping'))
            ->set('include_entity_links', $form_state->getValue('include_entity_links'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
