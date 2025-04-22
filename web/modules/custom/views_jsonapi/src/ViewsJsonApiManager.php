<?php

namespace Drupal\views_jsonapi;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service for managing Views JSON:API integration.
 */
class ViewsJsonApiManager {

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected $currentUser;

    /**
     * The renderer service.
     *
     * @var \Drupal\Core\Render\RendererInterface
     */
    protected $renderer;

    /**
     * The JSON:API resource type repository.
     *
     * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
     */
    protected $resourceTypeRepository;

    /**
     * Constructs a new ViewsJsonApiManager.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager.
     * @param \Drupal\Core\Session\AccountInterface $current_user
     *   The current user.
     * @param \Drupal\Core\Render\RendererInterface $renderer
     *   The renderer service.
     * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
     *   The JSON:API resource type repository.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountInterface $current_user,
        RendererInterface $renderer,
        ResourceTypeRepositoryInterface $resource_type_repository
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->renderer = $renderer;
        $this->resourceTypeRepository = $resource_type_repository;
    }

    /**
     * Gets a view by ID and display ID.
     *
     * @param string $view_id
     *   The view ID.
     * @param string $display_id
     *   The display ID.
     *
     * @return \Drupal\views\ViewExecutable
     *   The initialized view with the given display active.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *   Thrown when the view or display doesn't exist or is disabled.
     */
    public function getView($view_id, $display_id) {
        // Load the View entity using the entity type manager.
        $view_storage = $this->entityTypeManager->getStorage('view');
        $view_entity = $view_storage->load($view_id);

        if (!$view_entity) {
            throw new NotFoundHttpException("View $view_id not found.");
        }

        // Get the executable version of the view.
        $view = $view_entity->getExecutable();

        if (!$view->access($display_id)) {
            throw new NotFoundHttpException("Access denied for display $display_id on view $view_id.");
        }

        $view->setDisplay($display_id);
        return $view;
    }

    /**
     * Processes a view with the given request parameters.
     *
     * @param \Drupal\views\ViewExecutable $view
     *   The view to process.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The HTTP request.
     *
     * @return array
     *   The processed view results in a JSON:API compatible format.
     */
    public function processView(ViewExecutable $view, Request $request) {
        // Apply any filters from the request parameters
        $this->applyRequestFilters($view, $request);

        // Execute the view
        $view->execute();

        // Get the result rows
        $results = $this->getViewResults($view);

        // Format the results according to JSON:API spec
        return $this->formatJsonApiResponse($view, $results);
    }

    /**
     * Applies request filters to the view.
     *
     * @param \Drupal\views\ViewExecutable $view
     *   The view to apply filters to.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The HTTP request.
     */
    protected function applyRequestFilters(ViewExecutable $view, Request $request) {
        $query_params = $request->query->all();

        // Process filter parameters
        foreach ($query_params as $param => $value) {
            // Handle JSON:API filter params (filter[field][condition]=value)
            if (strpos($param, 'filter') === 0) {
                $this->processJsonApiFilter($view, $param, $value);
            }
            // Handle pagination params
            elseif ($param === 'page') {
                $this->processPagination($view, $value);
            }
            // Handle sorting params
            elseif ($param === 'sort') {
                $this->processSort($view, $value);
            }
        }
    }

    /**
     * Processes JSON:API filter parameters.
     *
     * @param \Drupal\views\ViewExecutable $view
     *   The view to apply the filter to.
     * @param string $param
     *   The filter parameter.
     * @param mixed $value
     *   The filter value.
     */
    protected function processJsonApiFilter(ViewExecutable $view, $param, $value) {
        // Parse the filter parameter like filter[field_name][operator]=value
        if (preg_match('/filter\[([^\]]+)\]\[([^\]]+)\]/', $param, $matches)) {
            $field_name = $matches[1];
            $operator = $matches[2];

            // Try to find a matching exposed filter in the view
            foreach ($view->display_handler->getHandlers('filter') as $filter_id => $filter) {
                if ($filter->options['exposed'] && $filter->realField === $field_name) {
                    // Apply the filter value
                    $view->exposed_raw_input[$filter->options['expose']['identifier']] = $value;
                    break;
                }
            }
        }
        // Handle simple filter[field_name]=value syntax
        elseif (preg_match('/filter\[([^\]]+)\]/', $param, $matches)) {
            $field_name = $matches[1];

            // Try to find a matching exposed filter in the view
            foreach ($view->display_handler->getHandlers('filter') as $filter_id => $filter) {
                if ($filter->options['exposed'] && $filter->realField === $field_name) {
                    // Apply the filter value
                    $view->exposed_raw_input[$filter->options['expose']['identifier']] = $value;
                    break;
                }
            }
        }
    }

    /**
     * Processes pagination parameters.
     *
     * @param \Drupal\views\ViewExecutable $view
     *   The view to apply pagination to.
     * @param mixed $value
     *   The pagination value, expected to be an array with 'offset' and 'limit'.
     */
    protected function processPagination(ViewExecutable $view, $value) {
        if (is_array($value)) {
            // Get pager plugin
            $pager = $view->display_handler->getPlugin('pager');

            // Handle page[offset] and page[limit]
            if (isset($value['offset']) && $pager && method_exists($pager, 'setOffset')) {
                $pager->setOffset((int) $value['offset']);
            }

            if (isset($value['limit']) && $pager && method_exists($pager, 'setItemsPerPage')) {
                $pager->setItemsPerPage((int) $value['limit']);
            }
        }
    }

    /**
     * Processes sort parameters.
     *
     * @param \Drupal\views\ViewExecutable $view
     *   The view to apply sorting to.
     * @param string $value
     *   The sort value, expected to be a comma-separated list of fields.
     */
    protected function processSort(ViewExecutable $view, $value) {
        $sorts = explode(',', $value);

        foreach ($sorts as $sort) {
            $direction = 'ASC';
            $field_name = $sort;

            // Handle descending sort with prefix -
            if (strpos($sort, '-') === 0) {
                $direction = 'DESC';
                $field_name = substr($sort, 1);
            }

            // Try to find a matching exposed sort in the view
            foreach ($view->display_handler->getHandlers('sort') as $sort_id => $sort_handler) {
                if ($sort_handler->realField === $field_name) {
                    $sort_handler->options['order'] = $direction;
                    break;
                }
            }
        }
    }

    /**
     * Gets the view results.
     *
     * @param \Drupal\views\ViewExecutable $view
     *   The executed view.
     *
     * @return array
     *   The processed view results.
     */
    protected function getViewResults(ViewExecutable $view) {
        $results = [];

        // Process each row
        foreach ($view->result as $row_index => $row) {
            // Check if this is an entity-based view with an entity in the row
            if (isset($row->_entity)) {
                $entity = $row->_entity;

                // Get the JSON:API resource type for this entity
                $resource_type = $this->resourceTypeRepository->get(
                    $entity->getEntityTypeId(),
                    $entity->bundle()
                );

                if (!$resource_type) {
                    continue;
                }

                // Build the resource object based on JSON:API spec
                $resource_object = [
                    'type' => $resource_type->getTypeName(),
                    'id' => $entity->uuid(),
                    'attributes' => [],
                ];

                // Add field values as attributes
                foreach ($view->field as $field_id => $field) {
                    // Skip non-visible fields
                    if (!$field->options['exclude']) {
                        $value = $view->style_plugin->getField($row_index, $field_id);
                        // Handle possible NULL values and convert to empty string
                        $resource_object['attributes'][$field_id] = $value !== NULL ? $value : '';
                    }
                }

                $results[] = $resource_object;
            } else {
                // Non-entity views (less common but possible)
                $item = [];

                foreach ($view->field as $field_id => $field) {
                    if (!$field->options['exclude']) {
                        $value = $view->style_plugin->getField($row_index, $field_id);
                        // Handle possible NULL values and convert to empty string
                        $item[$field_id] = $value !== NULL ? $value : '';
                    }
                }

                // For non-entity results, use a consistent resource type and ensure a valid ID
                $results[] = [
                    'type' => 'view-result',
                    'id' => (string) $row_index,
                    'attributes' => $item,
                ];
            }
        }

        return $results;
    }

    /**
     * Formats the results according to the JSON:API spec.
     *
     * @param \Drupal\views\ViewExecutable $view
     *   The executed view.
     * @param array $results
     *   The view results.
     *
     * @return array
     *   The JSON:API formatted response.
     */
    protected function formatJsonApiResponse(ViewExecutable $view, array $results) {
        // The primary data must always be either a single resource object
        // or an array of resource objects (or an empty array)
        $response = [
            'data' => $results,
        ];

        // Add meta data as a top-level member
        $response['meta'] = [
            'count' => count($results),
        ];

        // Add view information to meta if enabled in settings
        $config = \Drupal::config('views_jsonapi.settings');
        if ($config->get('include_view_metadata') ?? TRUE) {
            $response['meta']['view'] = [
                'id' => $view->id(),
                'display' => $view->current_display,
                'title' => $view->getTitle(),
            ];
        }

        // Add pagination links if available
        $pager = $view->display_handler->getPlugin('pager');
        if ($pager && method_exists($pager, 'usePager') && $pager->usePager()) {
            $links = $this->getPaginationLinks($view);
            if (!empty($links)) {
                $response['links'] = $links;
            }
        }

        return $response;
    }
    /**
     * Gets pagination links according to JSON:API spec.
     *
     * @param \Drupal\views\ViewExecutable $view
     *   The executed view.
     *
     * @return array
     *   The pagination links.
     */
    protected function getPaginationLinks(ViewExecutable $view) {
        $links = [];
        $pager = $view->display_handler->getPlugin('pager');

        if (!$pager) {
            return $links;
        }

        $current_page = $pager->getCurrentPage();
        $items_per_page = $pager->getItemsPerPage();

        // Some pager plugins might not have getTotalItems()
        $total_items = method_exists($pager, 'getTotalItems') ? $pager->getTotalItems() : 0;
        $total_pages = $total_items > 0 ? ceil($total_items / $items_per_page) : 0;

        // Base URL for links
        $resource = $this->entityTypeManager
            ->getStorage('views_jsonapi_resource')
            ->loadByProperties([
                'view_id' => $view->id(),
                'display_id' => $view->current_display,
            ]);

        // Get the first resource (there could be multiple)
        $resource = reset($resource);
        if (!$resource) {
            return $links;
        }

        $base_url = \Drupal::request()->getSchemeAndHttpHost() .
            \Drupal::request()->getBaseUrl() .
            '/jsonapi/' . $resource->getPath();

        // Self link (JSON:API requires links to be objects with an 'href' attribute)
        $links['self'] = [
            'href' => $base_url . '?page[offset]=' . ($current_page * $items_per_page) .
                '&page[limit]=' . $items_per_page,
        ];

        // First page
        $links['first'] = [
            'href' => $base_url . '?page[offset]=0&page[limit]=' . $items_per_page,
        ];

        // Last page (if we have it)
        if ($total_pages > 0) {
            $links['last'] = [
                'href' => $base_url . '?page[offset]=' . (($total_pages - 1) * $items_per_page) .
                    '&page[limit]=' . $items_per_page,
            ];
        }

        // Previous page
        if ($current_page > 0) {
            $links['prev'] = [
                'href' => $base_url . '?page[offset]=' . (($current_page - 1) * $items_per_page) .
                    '&page[limit]=' . $items_per_page,
            ];
        }

        // Next page
        if ($total_pages > 0 && $current_page < ($total_pages - 1)) {
            $links['next'] = [
                'href' => $base_url . '?page[offset]=' . (($current_page + 1) * $items_per_page) .
                    '&page[limit]=' . $items_per_page,
            ];
        }

        return $links;
    }
}
