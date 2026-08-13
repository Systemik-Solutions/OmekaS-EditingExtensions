# Editing Extensions

Project-specific extensions for the Systemik Solutions Omeka S installation.

Each feature can be enabled or disabled independently from the module's
configuration page. All features are enabled by default.

## Requirements

- Omeka S 4.x, version 4.1.1 or later

## Installation

The module directory must be named exactly `EditingExtensions`.

### Download

1. Download the module from the [GitHub repository](https://github.com/Systemik-Solutions/OmekaS-EditingExtensions).
2. Extract the downloaded archive into the `modules` directory of your Omeka S installation.
3. Rename the extracted directory to `EditingExtensions`.

### Git

Alternatively, clone the repository directly into the correctly named directory:

```sh
cd /path/to/omeka-s/modules
git clone https://github.com/Systemik-Solutions/OmekaS-EditingExtensions.git EditingExtensions
```

After installing the files, sign in to the Omeka S admin interface, open
**Modules**, and click **Install** for **Editing Extensions**.

## Recently edited item sorting

The module adds **Recently edited** to the sort selectors on:

- the admin item browse page (`/admin/item`);
- the admin item advanced search page (`/admin/item/search`).

Both controls submit Omeka's native `sort_by=modified` query parameter. Omeka
S 4.1.1 already supports this field in item API searches, so the same sorting
is available through:

```text
/api/items?sort_by=modified&sort_order=desc
```

No API override is required.

## Used terms in item advanced search

On the admin item advanced search page, the property and resource class
selectors are limited to terms used by existing resources.

The module caches the distinct IDs used by resources and applies those IDs to
Omeka's normal property and class queries. The cache is built lazily and
invalidated after successful resource create, update, batch, and delete API
operations. This avoids repeating Omeka's `used=true` joins and grouping over
the full value and resource tables every time the search form is rendered.

## Configuration

Configure the module at:

```text
/admin/module/configure?id=EditingExtensions
```

The available feature switches are:

- **Enable recently edited sorting**
- **Limit advanced search to used terms**
