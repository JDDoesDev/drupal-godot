<?php

namespace Drupal\views_jsonapi\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for creating and editing Views JSON:API resources.
 */
class ViewsJsonApiResourceForm extends EntityForm
{

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Constructs a new ViewsJsonApiResourceForm.
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
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);
        $entity = $this->entity;

        $form['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Label'),
            '#maxlength' => 255,
            '#default_value' => $entity->label(),
            '#description' => $this->t('Label for the Views JSON:API resource.'),
            '#required' => TRUE,
        ];

        $form['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $entity->id(),
            '#machine_name' => [
                'exists' => [$this, 'exist'],
            ],
            '#disabled' => !$entity->isNew(),
        ];

        // Get all available views for selection
        $view_storage = $this->entityTypeManager->getStorage('view');
        $views = $view_storage->loadMultiple();
        $view_options = [];

        foreach ($views as $view) {
            $view_options[$view->id()] = $view->label();
        }

        $form['view_id'] = [
            '#type' => 'select',
            '#title' => $this->t('View'),
            '#options' => $view_options,
            '#default_value' => $entity->get('view_id'),
            '#required' => TRUE,
            '#ajax' => [
                'callback' => '::updateDisplayOptions',
                'wrapper' => 'display-wrapper',
            ],
        ];

        $form['display_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'display-wrapper'],
        ];

        // Get the current view ID from form state or entity
        $view_id = $form_state->getValue('view_id') ?: $entity->get('view_id');

        // If we have a view ID, load the display options
        $display_options = [];
        if ($view_id) {
            $view = $view_storage->load($view_id);
            if ($view) {
                foreach ($view->get('display') as $display_id => $display) {
                    $display_options[$display_id] = $display['display_title'] . ' (' . $display_id . ')';
                }
            }
        }

        $form['display_wrapper']['display_id'] = [
            '#type' => 'select',
            '#title' => $this->t('Display'),
            '#options' => $display_options,
            '#default_value' => $entity->get('display_id'),
            '#required' => TRUE,
            '#empty_option' => $this->t('- Select a display -'),
            '#disabled' => empty($display_options),
        ];

        $form['path'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Path'),
            '#description' => $this->t('The path for this JSON:API resource (e.g., "views/articles"). Will be prefixed with /jsonapi/.'),
            '#default_value' => $entity->get('path') ?: 'views/' . ($view_id ? $view_id : '[view_id]') . '/' . ($entity->get('display_id') ?: '[display_id]'),
            '#required' => TRUE,
        ];

        $form['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#description' => $this->t('Description of this JSON:API resource.'),
            '#default_value' => $entity->get('description'),
        ];

        return $form;
    }

    /**
     * Ajax callback to update the display options when the view changes.
     */
    public function updateDisplayOptions(array &$form, FormStateInterface $form_state)
    {
        return $form['display_wrapper'];
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $entity = $this->entity;
        // Check if the entity is new *before* saving.
        $is_new = $entity->isNew();

        $status = $entity->save();

        $args = ['%label' => $entity->label()];
        // Use the isNew() check result instead of the save() return status.
        if ($is_new) {
            $this->messenger()->addStatus($this->t('Created new Views JSON:API resource %label.', $args));
        }
        else {
            $this->messenger()->addStatus($this->t('Updated Views JSON:API resource %label.', $args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
    }

    /**
     * Checks whether a resource with the given ID already exists.
     *
     * @param string $id
     *   The resource ID.
     *
     * @return bool
     *   TRUE if the resource exists, FALSE otherwise.
     */
    public function exist($id)
    {
        $entity = $this->entityTypeManager
            ->getStorage('views_jsonapi_resource')
            ->getQuery()
            ->condition('id', $id)
            ->execute();
        return (bool) $entity;
    }
}
