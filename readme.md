# Annotum Readme

## Background

Annotum is a WordPress theme (and child theme) built on the [Carrington theme engine](http://carringtontheme.com/).

All features are packaged within the theme. Once the theme is activated, many settings are available from the WordPress dashboard under the Annotum heading.

## Installation

To checkout the Annotum theme code to an existing WordPress installation:

1. Navigate to the wp-content/themes directory in the WP install
2. Create the annotum directory within the themes directory and change directory into it.
3. On the command line type `git clone git@github.com:Annotum/Annotum.git .` without backticks. 
3a. If you are using SVN: `svn co https://username@github.com/Annotum/Annotum.git .`

## Annotum in Your Language

Annotum has full support for internationalization. It also supports right-to-left languages. To get Annotum running in your language, you'll want to:

1. Get [WordPress in your language](http://codex.wordpress.org/WordPress_in_Your_Language).
2. Install your Annotum language pack in `annotum-base/languages/`. If you don't have a language pack for Annotum in your language, consider [creating a translation yourself](http://codex.wordpress.org/Translating_WordPress).

## Debugging

- Carrington theme debugging can be enabled/disabled in the functions.php file. This will output the file paths to all Carrington templates loaded into the page and is not recommended in non-development environments: `define('CFCT_DEBUG', true);`

## Roles and the Workflow

- When the workflow is active, the site-wide roles of `author` and `contributor` have the exact same capabilities on Articles. This does not carry over to any other post type.
- When the workflow is not active, the `author` and `contributor` roles should act according to their default WordPress capabilities. See [Roles and Capabilities](http://codex.wordpress.org/Roles_and_Capabilities) in the WordPress Codex for more information on the capabilities of these roles.

---
