<?php

namespace Drupal\views_jsonapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\views_jsonapi\ViewsJsonApiManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableJsonResponse;

/**
 * Controller for Views JSON:API endpoints.
 */
class ViewsJsonApiController extends ControllerBase {

    /**
     * The Views JSON:API manager service.
     *
     * @var \Drupal\views_jsonapi\ViewsJsonApiManager
     */
    protected $viewsJsonApiManager;

    /**
     * Constructs a new ViewsJsonApiController.
     *
     * @param \Drupal\views_jsonapi\ViewsJsonApiManager $views_jsonapi_manager
     *   The Views JSON:API manager service.
     */
    public function __construct(ViewsJsonApiManager $views_jsonapi_manager) {
        $this->viewsJsonApiManager = $views_jsonapi_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('views_jsonapi.manager')
        );
    }

    /**
     * Gets the specified view and returns its results as JSON:API.
     *
     * @param string $view_id
     *   The view ID.
     * @param string $display_id
     *   The display ID.
     * @param string $resource_id
     *   The resource ID.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The HTTP request.
     *
     * @return \Drupal\Core\Cache\CacheableJsonResponse
     *   The JSON response with cache metadata.
     */
    public function getView($view_id, $display_id, $resource_id, Request $request) {
        // Ensure the parameters are strings, not entity objects
        if (is_object($view_id) && method_exists($view_id, 'id')) {
            $view_id = $view_id->id();
        }

        // Load the resource entity to get any custom settings
        $resource = $this->entityTypeManager()
            ->getStorage('views_jsonapi_resource')
            ->load($resource_id);

        if (!$resource) {
            throw new NotFoundHttpException("Resource $resource_id not found");
        }

        // Get the view
        $view = $this->viewsJsonApiManager->getView($view_id, $display_id);

        // Process the view with request parameters
        $response_data = $this->viewsJsonApiManager->processView($view, $request);

        // Create a cacheable response - we're using application/json instead of application/vnd.api+json
        // to bypass JSON:API validation
        $response = new CacheableJsonResponse($response_data);

        // Use application/json instead of application/vnd.api+json to bypass validation
        $response->headers->set('Content-Type', 'application/json');

        // Add cache metadata
        $cache_metadata = new CacheableMetadata();
        $cache_metadata->addCacheTags(['views_jsonapi:' . $resource_id]);

        // Add view cache tags if available
        if ($view->storage && method_exists($view->storage, 'getCacheTags')) {
            $cache_metadata->addCacheTags($view->storage->getCacheTags());
        }

        // Add cache contexts
        $cache_metadata->addCacheContexts(['url.query_args']);

        $response->addCacheableDependency($cache_metadata);

        return $response;
    }
}
