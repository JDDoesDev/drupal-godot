<?php

namespace Drupal\views_jsonapi\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamically creates routes for Views JSON:API resources.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Load all configured JSON:API view resources
    $resources = $this->entityTypeManager
      ->getStorage('views_jsonapi_resource')
      ->loadMultiple();
    
    foreach ($resources as $resource) {
      $path = '/jsonapi/' . $resource->get('path');
      $route = new Route(
        $path,
        [
          '_controller' => '\Drupal\views_jsonapi\Controller\ViewsJsonApiController::getView',
          'view_id' => $resource->get('view_id'),
          'display_id' => $resource->get('display_id'),
          'resource_id' => $resource->id(),
        ],
        [
          '_permission' => 'access content',
        ],
        [
          // Important: Do not convert parameters to entity objects
          '_disable_route_normalizers' => TRUE,
        ]
      );
      
      $collection->add('views_jsonapi.resource.' . $resource->id(), $route);
    }
  }
}