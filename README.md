# WordpressPageModules
Wordpress plugin for allowing custom page elements to be designed in wp-admin and placed on any page. The plugin was developed for in-house use, and may lack documentation as a result.

## Installation
This plugin is not in the Wordpress plugin directory. To install, drag it into your `wp-content/plugins` folder and enable it in wp-admin.

## Concept
This plugin allows custom post types to be made fairly simply by adding parameters to an array. Additionally, templates can be assigned to the post types and be embedded on any page by choosing the post from a dropdown on the editor page. This functionality is known as a "page module", and has some neat features. Namely, once selected, all sub-pages can inherit a "module", or sub-pages can be assigned a different module.

## Use
### mm-custom-post-types.php
First, the custom post type array must be defined before the plugin is enabled. If modified, the plugin must be disabled and re-enabled to ensure user permissions are set.

The only part of the file that should be modified is the `self::$POST_TYPE_LIST` array defined within the `init_module_list()` function within the `Main` class on [line 250](https://github.com/mwmancuso/WordpressPageModules/blob/master/mm-custom-post-types/mm-custom-post-types.php#L250).

The structure of the array is as follows:
```php
self::$POST_TYPE_LIST == array(
    array(  // Custom post type/module definition 1
        'slug' => 'example',                            // Slug of custom post type (CPT)
        'name' => 'Examples',                           // Display name of CPT
        'singular_name' => 'Example',                   // Singular display name of CPT
        'roles' => array('administrator', 'editor'),    // WP roles that may modify this CPT
        'icon' => 'dashicons-slides',                   // Icon for display in WP Admin sidebar
        'rest' => true,                                 // Optional—whether CPT is available via WP REST
        'archive' => true,                              // Optional—whether CPT is given archive page
        'supports' => array('title', 'editor'),         // Optional—standard WP CPT "supports" values
        'taxonomies' => array(                          // Optional—custom taxonomies for CPT; created if don't exist
            array(
                'slug' => 'example-cat',
                'name' => 'Exampel Category',
                'hierarchical' => false,
                'rest' => true,                         // Whether TAXONOMY is available from WP REST
            ),
        ),
        'is_module' => true,                            // Whether CPT may be embedded in pages, see "Modules" below
        'template_path' => 'modules/example',           // Only if 'is_module' is true, defines where in template
                                                        // directory module template can be found. See "Templates" below
        'meta' => array(                                // Array of top-level meta fields
            array(  // Custom meta field 1
                'name' => 'example_meta',               // Top level meta-key
                'title' => 'Example Meta',              // Display name in admin
                'renderer' => new Table(                // Top-level renderer—typically "Table"; see "Renderers" below
                    array(                              // Array of renderers in table
                        // ... Renderer array, see "Renderers" below
                    ),
                    array(                              // Table settings
                        'has_types' => true,            // Example setting
                    ),
                )
            ),
            array(  // Custom meta field 2
                'name' => 'example_meta_2',             // Top level meta-key
                'title' => 'Example Meta 2',            // Display name in admin
                'renderer' => new Table(                // Top-level renderer—typically "Table"; see "Renderers" below
                    // ... Table settings
                )
            ),
        )
    ),
    array(  // Custom post type/module definition 2
        // ...
    ),
);
```

#### Renderers
Renderers define how the meta fields are displayed in the WP Admin section. When inside a table, renderers get their own nested meta value, and a display name associated with the renderer. For example, within a CPT/module definition (defined above),
```php
'meta' => array(
        array(  // Custom meta field 1
            'name' => 'example_meta',
            'title' => 'Example Meta',
            'renderer' => new Table(
                array(
                    array(
                        'name' => 'type',
                        'title' => 'Type',
                        'renderer' => new Type_Select(
                            array(
                                'title' => 'Title',
                                'image' => 'Image Background',
                            )
                        ),
                    ),
                    array(
                        'name' => 'title',
                        'title' => 'Title',
                        'renderer' => new Texturized_Text('Title'),
                        'options' => array(
                            'types' => array('image', 'title'),
                        ),
                    ),
                    array(
                        'name' => 'background',
                        'title' => 'Background Image',
                        'renderer' => new Background_Chooser(
                            'example_background',
                            'Example Background'
                        ),
                        'options' => array(
                            'types' => array('image'),
                        ),
                    ),
                ),
            array(  // Table settings
                'has_types' => true,    // Tells Table to look for meta field named 'type', with 'Type_Select' renderer
            ),
        )
    ),
)
```

Taking a look at this, we first see that the first renderer is of type `Type_Select`. This is used when the Table Settings option 'has_types' is true. The type selector renders as a dropdown box in the format of `'value' => 'Display Name'`. Then, the rest of the meta fields' "options" array must have a "types" option, with an array of which type values the field will be shown for. In this case, if the user selects "Title" for the type, only the "Title" meta field will show. If the user selects "Image Background", both meta fields will show.

The next meta field is the "Title" field, which has a simple `Texturized_Text` renderer. The single option for `Texturized_Text` is the placeholder value. The difference between `Texturized_Text` and `Plain_Text` is how the value will be displayed in the template file. If `Texturized_Text`, the WP Texturize pre-processor will be run on the text before it is sent to the template.

The third and final meta field, "Background Image", has the `Background_Chooser` renderer, which has some neat features. It will automatically create a Background Image category for uploads, allow the user to upload backgrounds on the spot, allows the background to be positioned, and allows the user to select previous background images. The "example_background" and "Example Background" refer to the background category's name and display name, respectively.

You can see all of the specific renderers and their settings in `inc/renderers`.

##### For Modules
Note that since the top-level is a meta, the `example_meta` field will be exported to the template file as a local variable. Thus, in the template file:
* `$example_meta` will contain all of the nested table data
* `$example_meta['type']` will contain the type value of the module
* `$example_meta['title']` will contain the texturized output of the value for this field
* `$example_meta['background']['id']` will contain the ID, if available, of the selected background image
* `$example_meta['background']['position']` will contain the position, if available, of the selected background image
