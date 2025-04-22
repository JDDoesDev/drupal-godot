<?php

namespace Drupal\views_jsonapi;

use Drupal\Core\Form\FormStateInterface;

/**
 * Helper class for Views UI integration.
 */
class ViewsUiHelper
{

    /**
     * Alters the Views UI form to add JSON:API options.
     *
     * @param array $form
     *   The form array.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The form state.
     */
    public static function alterViewsUiForm(array &$form, FormStateInterface $form_state)
    {
        // Only add to the edit display form
        if (isset($form['options']) && isset($form['options']['#title']) && $form['options']['#title'] === 'Basic settings') {
            $view = $form_state->get('view');

            // Add the 'jsonapi' tag option
            $form['options']['jsonapi'] = [
                '#type' => 'details',
                '#title' => \Drupal::translation()->translate('JSON:API settings'),
                '#weight' => 5,
                '#open' => TRUE,
            ];

            $form['options']['jsonapi']['enable_jsonapi'] = [
                '#type' => 'checkbox',
                '#title' => \Drupal::translation()->translate('Expose this view as a JSON:API endpoint'),
                '#description' => \Drupal::translation()->translate('If checked, this view will be available as a JSON:API endpoint at /jsonapi/views/{view_id}/{display_id}.'),
                '#default_value' => $view->hasTag('jsonapi'),
            ];

            // Add submit handler
            $form['actions']['submit']['#submit'][] = [static::class, 'submitViewsUiForm'];
        }
    }

    /**
     * Submit handler for the Views UI form.
     *
     * @param array $form
     *   The form array.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The form state.
     */
    public static function submitViewsUiForm(array &$form, FormStateInterface $form_state)
    {
        $view = $form_state->get('view');

        // Update the view tags based on the checkbox value
        if ($form_state->getValue(['options', 'jsonapi', 'enable_jsonapi'])) {
            $view->addTag('jsonapi');
        } else {
            $view->removeTag('jsonapi');
        }
    }
}
