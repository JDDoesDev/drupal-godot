# Views JSON:API

## Overview

Views JSON:API is a Drupal 11 module that exposes Drupal Views to JSON:API with the same filtering and formatting capabilities as the View it's exposing. This allows you to leverage Drupal's powerful Views module for data selection, filtering, and formatting while delivering the data in a standardized JSON:API format for consumption by frontend applications or other services.

## Requirements

- Drupal 11.x
- Views module
- JSON:API module

## Installation

1. Install the module via Composer:
   ```
   composer require drupal/views_jsonapi
   ```

2. Enable the module:
   ```
   drush en views_jsonapi
   ```

## Configuration

### Creating a Views JSON:API Resource

1. Go to **Admin > Configuration > Web services > Views JSON:API Resources** (`/admin/config/services/views-jsonapi`).
2. Click **Add Views JSON:API Resource**.
3. Fill in the form:
   - **Label**: A human-readable name for the resource
   - **View**: Select the View you want to expose
   - **Display**: Select which display of the View to expose
   - **Path**: The path for the JSON:API endpoint (will be prefixed with `/jsonapi/`)
   - **Description**: Optional description of the resource
4. Click **Save**.

### Module Settings

- Go to **Admin > Configuration > Web services > Views JSON:API Settings** (`/admin/config/services/views-jsonapi/settings`) to configure general settings.

## Usage

Once you've created a Views JSON:API Resource, it will be available at:

```
/jsonapi/{resource_path}
```

For example, if you created a resource with the path `views/articles`, the endpoint would be:

```
/jsonapi/views/articles
```

### Filtering

You can use standard JSON:API filter parameters to filter the data:

- Simple filter: `?filter[field_name]=value`
- Operator filter: `?filter[field_name][operator]=value`

These filter parameters will be mapped to the exposed filters configured in your View.

### Pagination

Use standard JSON:API pagination parameters:

- Offset pagination: `?page[offset]=0&page[limit]=10`

### Sorting

Use standard JSON:API sort parameters:

- Ascending sort: `?sort=field_name`
- Descending sort: `?sort=-field_name`
- Multiple fields: `?sort=field1,-field2`

## Examples

### Basic request

```
GET /jsonapi/views/articles
```

### Filtered request

```
GET /jsonapi/views/articles?filter[title]=Drupal&filter[created][gt]=2025-01-01
```

### Paginated request

```
GET /jsonapi/views/articles?page[offset]=10&page[limit]=5
```

### Sorted request

```
GET /jsonapi/views/articles?sort=-created,title
```

## Response Format

The module returns JSON:API compliant responses:

```json
{
  "data": [
    {
      "type": "node--article",
      "id": "03ab6c92-4f0e-41a4-b4c2-0a29ce7f32b6",
      "attributes": {
        "title": "Example Article",
        "created": "2025-03-15T14:30:00",
        "body": "This is the content of the article..."
      },
      "relationships": {
        "author": {
          "data": {
            "type": "user--user",
            "id": "83a5b4d1-c34e-4a81-9e7b-5a7a0e28bb6e"
          }
        }
      }
    }
  ],
  "meta": {
    "count": 1,
    "view": {
      "id": "content",
      "display": "page_1",
      "title": "Content"
    }
  },
  "links": {
    "self": "http://example.com/jsonapi/views/articles?page[offset]=0&page[limit]=10",
    "first": "http://example.com/jsonapi/views/articles?page[offset]=0&page[limit]=10",
    "last": "http://example.com/jsonapi/views/articles?page[offset]=90&page[limit]=10",
    "next": "http://example.com/jsonapi/views/articles?page[offset]=10&page[limit]=10"
  }
}
```

## Troubleshooting

- **No endpoint available**: Make sure you've created a Views JSON:API Resource for your View.
- **Access denied**: Check the permissions required to access the View.
- **Filters not working**: Ensure that the filters you're trying to use are exposed in the View.

## License

This project is licensed under the GPL v2 or later.