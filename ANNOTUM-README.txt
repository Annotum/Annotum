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

## Debugging

- Carrington theme debugging can be enabled/disabled in the functions.php file. This will output the file paths to all Carrington templates loaded into the page and is not recommended in non-development environments: `define('CFCT_DEBUG', true);`

---
