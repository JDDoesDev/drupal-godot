<?php

namespace Drupal\views_jsonapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Views JSON:API Resource entity.
 *
 * @ConfigEntityType(
 *   id = "views_jsonapi_resource",
 *   label = @Translation("Views JSON:API Resource"),
 *   handlers = {
 *     "list_builder" = "Drupal\views_jsonapi\ViewsJsonApiResourceListBuilder",
 *     "form" = {
 *       "default" = "Drupal\views_jsonapi\Form\ViewsJsonApiResourceForm",
 *       "delete" = "Drupal\views_jsonapi\Form\ViewsJsonApiResourceDeleteForm"
 *     }
 *   },
 *   config_prefix = "resource",
 *   admin_permission = "administer views_jsonapi",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/config/services/views-jsonapi/add",
 *     "edit-form" = "/admin/config/services/views-jsonapi/{views_jsonapi_resource}/edit",
 *     "delete-form" = "/admin/config/services/views-jsonapi/{views_jsonapi_resource}/delete",
 *     "collection" = "/admin/config/services/views-jsonapi"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "view_id",
 *     "display_id",
 *     "path",
 *     "description"
 *   }
 * )
 */
class ViewsJsonApiResource extends ConfigEntityBase
{

    /**
     * The Views JSON:API Resource ID.
     *
     * @var string
     */
    protected $id;

    /**
     * The Views JSON:API Resource label.
     *
     * @var string
     */
    protected $label;

    /**
     * The View ID.
     *
     * @var string
     */
    protected $view_id;

    /**
     * The View display ID.
     *
     * @var string
     */
    protected $display_id;

    /**
     * The resource path.
     *
     * @var string
     */
    protected $path;

    /**
     * The resource description.
     *
     * @var string
     */
    protected $description;

    /**
     * Gets the View ID.
     *
     * @return string
     *   The View ID.
     */
    public function getViewId()
    {
        return $this->view_id;
    }

    /**
     * Gets the Display ID.
     *
     * @return string
     *   The Display ID.
     */
    public function getDisplayId()
    {
        return $this->display_id;
    }

    /**
     * Gets the path.
     *
     * @return string
     *   The path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Gets the description.
     *
     * @return string
     *   The description.
     */
    public function getDescription()
    {
        return $this->description;
    }
}
