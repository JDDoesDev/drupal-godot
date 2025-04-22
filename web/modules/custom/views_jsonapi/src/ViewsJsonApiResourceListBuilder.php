<?php

namespace Drupal\views_jsonapi;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Views JSON:API Resource entities.
 */
class ViewsJsonApiResourceListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['label'] = $this->t('Label');
        $header['view'] = $this->t('View');
        $header['display'] = $this->t('Display');
        $header['path'] = $this->t('JSON:API Path');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        $row['label'] = $entity->label();
        $row['view'] = $entity->get('view_id');
        $row['display'] = $entity->get('display_id');
        $row['path'] = '/jsonapi/' . $entity->get('path');
        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOperations(EntityInterface $entity)
    {
        $operations = parent::getDefaultOperations($entity);

        // Add a "Test" operation to quickly view the JSON:API resource
        $operations['test'] = [
            'title' => $this->t('Test'),
            'weight' => 100,
            'url' => Url::fromUri('base:/jsonapi/' . $entity->get('path')),
        ];

        return $operations;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $build = parent::render();
        $build['table']['#empty'] = $this->t('No Views JSON:API Resources available. <a href="@link">Add Views JSON:API Resource</a>.', [
            '@link' => Url::fromRoute('entity.views_jsonapi_resource.add_form')->toString(),
        ]);

        // Add action links
        $build['#attached']['library'][] = 'views_jsonapi/admin';

        return $build;
    }
}
