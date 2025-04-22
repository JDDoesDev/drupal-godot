<?php

namespace Drupal\views_jsonapi\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form for deleting a Views JSON:API Resource.
 */
class ViewsJsonApiResourceDeleteForm extends EntityConfirmFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t('Are you sure you want to delete the Views JSON:API resource %name?', ['%name' => $this->entity->label()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('entity.views_jsonapi_resource.collection');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText()
    {
        return $this->t('Delete');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->entity->delete();
        $this->messenger()->addStatus($this->t('The Views JSON:API resource %label has been deleted.', ['%label' => $this->entity->label()]));

        // Rebuild routes to remove this resource's route
        \Drupal::service('router.builder')->rebuild();

        $form_state->setRedirectUrl($this->getCancelUrl());
    }
}
