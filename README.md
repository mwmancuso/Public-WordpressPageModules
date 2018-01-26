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

### Renderers
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

#### For Modules
Note that since the top-level is a meta, the `example_meta` field will be exported to the template file as a local variable. Thus, in the template file:
* `$example_meta` will contain all of the nested table data
* `$example_meta['type']` will contain the type value of the module
* `$example_meta['title']` will contain the texturized output of the value for this field
* `$example_meta['background']['id']` will contain the ID, if available, of the selected background image
* `$example_meta['background']['position']` will contain the position, if available, of the selected background image

### Modules
Modules are a specific custom post type that may be embedded within pages. Once a custom post type defined with this plugin is declared as a module, the plugin will add a box to the right-hand side of the page editor which allows the user to select which module (custom post) to embed in the page.

The plugin is meant to handle most cases of hierarchal page structures. On every page, the user has the following options:
* Inherit the selected module of the parent
* Display no module at all (overrides all other options)
* Select from the list of available modules (custom posts)
* Different behavior for children (checkbox)
  * Inherit
  * None
  * Select from list

Thus, for the example of a custom page header, the following situation becomes trivial:
1. Define a new custom post type for headers and declare it as a module
2. Add a template for the header module (see below)
  * The code given is already set up for this behavior
3. Have a sub-section of the site called "About"
4. Create one header (add a new header post) for the "About" home page, and a separate header for the children of the "About" page
5. Edit the about page
6. Select the About Home Page header
7. Select "Different behavior for children."
8. Select the About Internal Page header for children
With this, the "About" page will have one header, while all of its children will have another header—all defined on one page.

Due to the inheritance process, children of children will also get the About Internal Page header.

When defining a new custom post type in `mm-custom-post-types.php`'s `$POST_TYPE_LIST`, the following settings must be added to fully declare the post type as a module:

```php
'is_module' => true,                            // Whether CPT may be embedded in pages, see "Modules" below
'template_path' => 'modules/example',           // Only if 'is_module' is true, defines where in template
```

### Templates
When defining a new custom post type in `mm-custom-post-types.php`'s `$POST_TYPE_LIST`, the following settings must be added to fully declare the post type as a module:

```php
'is_module' => true,                            // Whether CPT may be embedded in pages, see "Modules" below
'template_path' => 'modules/example',           // Only if 'is_module' is true, defines where in template
```

The `template_path` variable is relative to the theme's directory. Thus, if the current theme is in `wp-content/themes/example_theme/`, the above template path will refer to `wp-content/themes/example_theme/modules/example.php`.

Within `example.php`, the following basic code is required/recommended:

```php
<?php
if (!isset($module_is_inherited)) $module_is_inherited = false;
if (!isset($module_title)) $module_title = '';
if (!isset($module_slug)) $module_slug = '';
if (!isset($module_id)) $module_id = 0;
if (!isset($header_info)) $example_meta = array();
?>
<!-- Example Module -->
<!-- Example Module Name: <?php echo $module_title; ?> -->
```

Notice that the `$example_meta` field is extracted from the top-level meta definition from above. Using the custom post type defined earlier, the `$example_meta` field has the following format:
* `$example_meta` will contain all of the nested table data
* `$example_meta['type']` will contain the type value of the module
* `$example_meta['title']` will contain the texturized output of the value for this field
* `$example_meta['background']['id']` will contain the ID, if available, of the selected background image
* `$example_meta['background']['position']` will contain the position, if available, of the selected background image

The module can then be defined with any HTML and PHP needed to create the module. The contents of the template will be displayed as-is when embedded into a page, with no surrounding tags or formatting.

#### Include the Module Template
The developer may embed a module inside any page template. The specific module selected for the page will be automatically determined by the plugin, and the module template will receive the specific data for the requested page. To embed a module anywhere in a template, use the following code:

```php
<?php
global $mm_custom_post_types;
if ($mm_custom_post_types) $mm_custom_post_types->get_module('example');
?>
```

If a module is defined or inherited for the requested page, the plugin will find all of the metadata for the module, pass it to the module template, render the module template, and print it at the location of this block. If no module is found, the plugin will simply print nothing.

A default module can also be defined by module slug or id:

```php
<?php
global $mm_custom_post_types;
if ($mm_custom_post_types) $mm_custom_post_types->get_module('example', array('default_slug' => 'default');
?>
```

```php
<?php
global $mm_custom_post_types;
if ($mm_custom_post_types) $mm_custom_post_types->get_module('example', array('default_id' => 123);
?>
```

## Example
### mm-custom-post-types.php
For the example, we'll consider a custom header (think: hero image with background image and title) that may be embedded on any page. First, we'll need to define `$POST_TYPE_LIST` in `mm-custom-post-types.php`:

```php
self::$POST_TYPE_LIST = array(
    array(
        'slug' => 'header',
        'name' => 'Headers',
        'singular_name' => 'Header',
        'roles' => array('administrator', 'editor'),
        'icon' => 'dashicons-slides',
        'template_path' => 'template-parts/modules/header',
        'is_module' => true,
        'meta' => array(
            array(
                'name' => 'header_info',
                'title' => 'Header Info',
                'renderer' => new Table(
                    array(
                        array(
                            'name' => 'type',
                            'title' => 'Type',
                            'renderer' => new Type_Select(
                                array(
                                    'title' => 'Title',
                                    'image' => 'Image Background',
                                    'video' => 'Video Background',
                                )
                            ),
                        ),
                        array(
                            'name' => 'title',
                            'title' => 'Title',
                            'renderer' => new Texturized_Text('Title'),
                            'options' => array(
                                'types' => array('image', 'video', 'title'),
                            ),
                        ),
                        array(
                            'name' => 'sub-title',
                            'title' => 'Title',
                            'renderer' => new Texturized_Text('Pre-Title'),
                            'options' => array(
                                'types' => array('image', 'video', 'title'),
                            ),
                        ),
                        array(
                            'name' => 'background',
                            'title' => 'Background Image',
                            'renderer' => new Background_Chooser(
                                'header_background',
                                'Header Background'
                            ),
                            'options' => array(
                                'types' => array('image'),
                            ),
                        ),
                        array(
                            'name' => 'background-video',
                            'title' => 'Background Video',
                            'renderer' => new Table(
                                array(
                                    array(
                                        'name' => 'mp4',
                                        'title' => 'MP4 File',
                                        'renderer' => new Plain_Text('MP4 File URL'),
                                    ),
                                    array(
                                        'name' => 'ogv',
                                        'title' => 'OGV File',
                                        'renderer' => new Plain_Text('OGV File URL'),
                                    ),
                                )
                            ),
                            'options' => array(
                                'types' => array('video'),
                            ),
                        ),
                        array(
                            'name' => 'content',
                            'title' => 'Contents',
                            'renderer' => new Text_Editor(
                                array(
                                    'teeny' => true,
                                    'media_buttons' => false,
                                    'textarea_rows' => 4,
                                )
                            ),
                            'options' => array(
                                'types' => array('image', 'video'),
                            ),
                        ),
                    ),
                    array(
                        'has_types' => true,
                    )
                ),
            ),
        ),
    ),
);
```

Once this is set up, the plugin may be enabled.

Once enabled, a new post type called "Headers" in the WP Admin will be available to anybody with the roles "Administrator" or "Editor".

After clicking "Add New Header", the user will be presented with a metabox called "Header Info" as defined above.

Inside the "Header Info" box will be a table.

Inside the table will be a textbox named "Type" which will default to "None". Selecting "Title", "Image Background" or "Video Background" will determine which set of the remaining fields will show. They are defined by each of the table element's settings, i.e. `'types' => array('image', 'video')` indicates that the field will only show if "Image Background" or "Video Background" are selected.

For use in this exmaple, we'll assume that three Header posts have been created:
* Default (no Title specified for this Header)
* About Home (Title is "About Us" for this Header)
* About Internal (no Title specified for this Header")

Next, we'll need to define the module template for the module.

### Template
Since the above template path was defined as `template-parts/modules/header`, the following file must reside at `wp-content/themes/YourTheme/template-parts/modules/header.php`.

```php
<?php
if (!isset($module_is_inherited)) $module_is_inherited = false;
if (!isset($module_title)) $module_title = '';
if (!isset($module_slug)) $module_slug = '';
if (!isset($module_id)) $module_id = 0;
if (!isset($header_info)) $header_info = array();
?>
<!-- Page Header -->
<!-- Header Name: <?php echo $module_title; ?> -->
<header>
    <?php if ($header_info['type'] == 'image') { ?>
        <?php
        $background_data = wp_get_attachment_image_src($header_info['background']['id'], 'header_background');
        $background_url = $background_data[0];
        ?>
        <div class="background-image" style="background-image: url('<?php echo $background_url; ?>');"></div>
    <?php } else if ($header_info['type'] == 'video') { ?>
        <video class="background-video" autoplay loop muted class="hero-embed" poster="<?php echo get_template_directory_uri(); ?>/images/stock-video-background.jpg">
            <source src="<?php echo $header_info['background-video']['mp4']; ?>" type="video/mp4">
            <source src="<?php echo $header_info['background-video']['ogv']; ?>" type="video/ogv">
        </video>
    <?php } ?>
    <div class="container">
        <h1>
        <?php
        if ($header_info['title']) {
            echo $header_info['title']; // This will be the Texturized Text result
        } else {
            echo get_the_title(); // This will be the page title
        }
        </h1>
        <?php if ($header_info['sub-title']) { ?>
            <h2><?php echo $header_info['sub-title']; ?></h2>
        <?php } ?>
    </div>
</header>
```

We will assume that the CSS for `.background-image` and `.background-video` are properly set up to position the background image and videos behind the text.

### Page Template
This example will apply the header to all pages. Thus, the following code belongs in `wp-content/themes/YourTheme/page.php`.

```php
<?php
get_header(); ?>

<?php
global $mm_custom_post_types;
if ($mm_custom_post_types) $mm_custom_post_types->get_module('header', array('default_slug' => 'default'));
?>

    <main>
        <section>
            <div class="container">
                <?php
                while (have_posts()) : the_post();

                    the_content();

                endwhile;
                ?>
            </div>
        </section>
    </main>

<?php get_footer();
```

The header, if it exists, will effectively be placed before the main content. Since we specify the option `'default_slug' => 'default'`, as long as a Header with the slug "default" exists, every page will have a header unless it is specifically set to "None" on the page editor page.

### Page Editor
Finally, let's assume the page structure:

* Home Page
* About Home
  * About the Owner
  * About the Company
  * About the Products
    * Product 1
    * Product 2
* Contact Us

If we edit the About Home page to use the Header called "About Home", and select the checkbox to use different behavior for children, then select "About Internal" for the children, we will have the desired behavior. The headers will be applied like so:

* Home Page - Default Header (will just display "Home Page")
* About Home - About Home Header (will display "About Us")
  * About the Owner - About Internal Header
  * About the Company - About Internal Header
  * About the Products - About Internal Header
    * Product 1 - About Internal Header
    * Product 2 - About Internal Header
* Contact Us - Default Header (will just display "Contact Us")
