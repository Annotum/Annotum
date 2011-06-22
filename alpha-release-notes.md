# Annotum - Release Notes for Alpha Version

## About this version

Annotum Alpha is the initial release version of the Annotum WordPress theme.  It may contain bugs and should not be used for a production system.

You will need a functioning installation of WordPress to use Annotum -- on your own server or on a managed hosting service that allows you to install themes and plugins.  

## Obtaining the code

To obtain the alpha distribution, download it from [GitHub](https://github.com/Annotum/Annotum/downloads). You want the file called `annotum-alpha-distribution.zip` for this release.

Unzip the file to a local folder; you'll see two zip files: annotum-base.cip and current.zip -- these are the main Annotum theme and child theme respectively.

Follow the steps below to install the themes.

## Installation

To add the Annotum theme to an existing WordPress installation:

1. Go to your Dashboard, navigate to `Appearance > Themes`, and click to the `Install Themes` tab.
2. Use the `Upload` link to upload annotum-base.zip and current.zip from the distribution file.
3. Activate the Annotum 0.1 theme. 
4. On the `Annotum Settings` page, you'll want to enable workflow and (optionally) workflow notifications.

> Note: workflow notifications can generate A LOT OF EMAIL, one message to every editor and author for every workflow action. 

## Getting Started

To get started using Annotum, you'll need to set up your theme, add some users, create articles, and process the articles via the workflow.

### Theme Setup

Annotum uses standard WordPress features such as widgets and menus for customization.  There are also some Theme Settings such as callouts.  In a future version, multiple color schemes will be available, but for now you'll have to tweak the CSS to change the colors.

> Note: because full equation support is not yet implemented in Annotum, you may want to enable the WordPress JetPack plugin set to take advantage of the built-in LaTeX equation rendering.

### Adding Users

Annotum's workflow is based around the `Articles` custom post type and a few custom user permission levels: Author, Reviewer, Editor, and Admin. 

1. Admin aka publication staff.  Admins can perform all functions and see all comments.  They can add and remove reviewers and coauthors, change authors, approve/reject/request revisions on articles, and publish approved articles.  Create admins by granting them the "Admin"  WordPress user level.
2. Editor.  Editors can do everything except publish articles (move from `review complete/approved` to `published`). Create Editors by granting them the "Editor" WordPress user level.
3. Author.  Authors can create articles, add and remove coauthors, and submit articles for review. Authors cannot see review comments on their articles, nor do they see who the reviewers are.  Authors can be added with either the "Contributor" or "Author" WordPress user level.  If "Author" is used, the user will be able to create regular posts and pages via the built-in WordPress functions.  Note that Co-authors are defined at the article level (not user level) when they are added by the article author. They can be any level from Contributor on up.
4. Reviewer. Reviewers can submit review comments and view replies to their own review comments only. Reviewers are defined at the article level when they are added by the editor. They can be any level from Contributor on up. 
5. Subscriber.  Essentially a site viewer, they can make comments on published articles.

### Creating Articles

Create a new article via the "new article" button from the "Articles" dashboard.  You'll see various sections for adding co-authors, including sections of your article, etc.  Note that future versions will have a greatly-enhanced set of authoring controls including the ability to import and export XML, ensure conformance with the NLM DTD, and other features such as robust handling of references.

Once you'ce created an article, you can:

1. Preview the article
2. Add Co-authors (once you do, they can also edit the article)
3. Submit the article for review

### Using Workflow 

The basic workflow is:
1. Author (and potentially co-authors) create and edit their article.  All co-authors can post internal comments on the article during this process.
2. When ready, the Author submits the article for review. 
3. The editor assigns reviewers, each of whom can enter review comments and a recommendation: Approve, Reject, or Request Revisions.  The editor may reply to review comments; reviewers can only see their own comments and replies to them.
4. When the reviews are in, the editor can Approve, Reject, or Request Revsions. 
5. Once an article is approved, any admin can publish it to the site.

## Additional Notes

Support.  Annotum is in a pre-release state so support is limited to the [Annotum Discussion List](https://groups.google.com/group/annotum). Please feel free to submit questions, bug reports, or suggestions there.

