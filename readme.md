# Annotum Readme

## Background

Annotum is a WordPress theme (and child theme) built on the [Carrington theme engine](http://carringtontheme.com/).

All features are packaged within the theme. Once the theme is activated, many settings are available from the WordPress dashboard under the Annotum heading.

## Requirements

If you host your own Annotum-powered site using WordPress, the following are the minimum requirements necessary:

- WordPress version 3.3
- PHP 5.2.4 (including libxml 2.6.29 or higher)

## Installation

To check out the Annotum theme code to an existing WordPress installation:

1. Navigate to the `wp-content/themes` directory in your WordPress installation
2. On the command line type: `git clone git@github.com:Annotum/Annotum.git annotum` without backticks. 

    > Note: for read-only access, use `git clone git://github.com/Annotum/Annotum.git annotum`. If you wish read-write access please let us know at info at annotum.org.
3. Or, if you prefer to use subversion: `svn co https://username@svn.github.com/Annotum/Annotum.git annotum`
4. That's it! Log in to your WordPress administrator dashboard, navigate to `Appearance > Themes` and Activate the theme

### Annotum in Your Language

Annotum has full support for internationalization. It also supports right-to-left languages. To get Annotum running in your language, you'll want to:

1. Get [WordPress in your language](http://codex.wordpress.org/WordPress_in_Your_Language).
2. Install your Annotum language pack in `annotum-base/languages/`. If you don't have a language pack for Annotum in your language, consider [creating a translation yourself](http://codex.wordpress.org/Translating_WordPress).

## Feature Notes

### User Roles and the Workflow (authors, contributors)

When the workflow is active, the site-wide roles of `author` and `contributor` have the exact same capabilities on the Articles post-type. This is not the case with any other post type.

When the workflow is not active, the `author` and `contributor` roles should act according to their default WordPress capabilities. See [Roles and Capabilities](http://codex.wordpress.org/Roles_and_Capabilities) in the WordPress Codex for more information on the capabilities of these roles.

### Header Image

You can upload a custom header image, replacing the site title that normally shows up in the header. In the current version of WordPress, header images are required to have fixed dimensions. This is slated to change with future versions of WordPress, but for now practical rules for adding a header image are:

- Header images are required to be a fixed width and height.
- The required width/height of header images is 500x52; it's extra long so longer logos will fit.
- Uploaded images that are larger will prompt you to crop them, using a tool in the settings screen.
- If your logo has transparency (`.gif` or `.png`), make sure you upload a version that is *exactly* 500x52. WordPress has limited support for image scaling, so if it has to scale your image, you'll lose the transparency.

When a custom image is defined, it will replace the text in the header.

### Home Page Callouts

You can optionally add 1 or 2 callouts to the home page, that contain announcements, notices or other important info.

To set up callouts, log in to the WordPress admin and go to `Appearance > Theme Options`. You'll see two groups of fields, "Home Page Callout Left" and "Home Page Callout Right". Each has three fields, all optional:

- Title: display a title in the callout.
- URL: a valid URL. If provided, the title of the callout will be linked to this address.
- Content: Free-form content. HTML is OK.

## For Theme Developers

Annotum has been developed to be easy to customize with [WordPress Child Themes](http://codex.wordpress.org/Child_Themes).

- All assets are registered via `wp_enqueue` so you can enqueue/dequeue any CSS or JS file.

- Custom template tags are set in `templates.php` to make it easy to access custom functionality like acknowledgements, funding statements, Tweet buttons, etc.

- You can easily customize template tags by extending the class that contains the template tag library (`Anno_Template_Tags`). An easy way to do this is to register your own template tag class after `init:10`. Example:

        class My_Template_Tags extends Anno_Template_Tags { /* Define something... */ }
        function my_init_template_tags() {
            $my_template = new My_Template_Tags();
            Anno_Keeper::keep('template', $my_template);
        }
        add_action('init', 'my_init_template_tags', 11);

### Filters

Annotum introduces various filters for further customization

- `anno_user_meta` defines additional user meta information to store. This data is stored in an array using the array key as the meta key and the value as the label
- `anno_profile_fields_title` Defines the heading text on the profile edit page for additional meta fields
- `anno_user_meta_display` Defines which meta (from anno_user_meta) will be displayed in the hover card in the authors section of an article
- `anno_valid_dtd_elements` Filter for valid dtd elements in the tinyMCE editor
- `annowf_notification_recipients` Determines recipients of a notification
- `annowf_notfication` A filter for notifications with the ability to customize notification subject and bodies, 


## Debugging

- Carrington Core theme engine debugging can be enabled/disabled in the functions.php file. This will output the file paths to all Carrington templates loaded into the page and is not recommended in non-development environments: `define('CFCT_DEBUG', true);`
- Some of the expensive `template.php` tags use [transient caching](http://codex.wordpress.org/Transients_API) to make sure things are speedy. If you need to troubleshoot transient caching, you can set `Anno_Template::$use_caches` to `false`. Setting `CFCT_DEBUG` to `true` will also disable the transient caches.

## Support

For support information, please refer to http://annotum.org/support/

## Credits

Annotum is a production of [Solvitor LLC](http://solvitor.com) with heavy lifting provided by [Crowd Favorite](http://crowdfavorite.com), and special thanks to: Google, PLoS, NIH/NLM/NCBI, and Automattic.

Annotum is free (speech and beer)

---
 