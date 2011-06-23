# Annotum - Release Notes for Alpha Version

## About this version

Annotum Alpha is the initial release version of the Annotum WordPress theme.  It may contain bugs and should not be used for a production system.

Please note that certain sigificant pieces of functionality, in particular the authoring tools, conformance with document structure, and the import/export of XML are not implemented in this Alpha release.  The workflow elements are *mostly* implemented but some gaps remain.  

> Note: because full equation support is not yet implemented in Annotum, you may want to enable the WordPress JetPack plugin set to take advantage of its built-in LaTeX equation rendering.

You will need a functioning installation of WordPress to use Annotum -- on your own server or on a managed hosting service that allows you to install themes and plugins.  

> It is recommended that you install Annotum Alpha in a new, empty, test WordPress installation.

## Obtaining the code

To obtain the alpha distribution, download it from [GitHub](https://github.com/Annotum/Annotum/downloads). You want the file called `annotum-alpha-distribution.zip` for this release.

Unzip the file to a local folder; you'll see two zip files: annotum-base.cip and current.zip -- these are the main Annotum theme and child theme respectively.

## Installation
To add the Annotum theme to an existing WordPress installation:

1. Go to your Dashboard, navigate to `Appearance > Themes`, and click to the `Install Themes` tab.
2. Use the `Upload` link to upload `annotum-base.zip` and `current.zip` from the distribution file.
3. Activate the Annotum 0.1 theme. 
4. From the Themes dashboard, click `Annotum Settings`.  You'll want to enable workflow and (optionally) workflow notifications.  The `Allow article authors to see reviewers` option does just that; otherwise all reviewers' comments and identities are hidden from the authors.
5. Optionally go to `Theme Settings` and enter any desired options (e.g. Home page callouts, Google Analytics code).

> Note: workflow notifications generate an email message to every editor and author for every workflow action. This can generate quite a bit of email on an active site!

## Getting Started

To get started using Annotum, you'll need to set up your theme, add some users, create articles, and process the articles via the workflow.

### Theme Setup

Annotum uses standard WordPress features such as widgets and menus for customization.  There are also some Theme Settings such as callouts.  In a future version, multiple color schemes will be available, but for now you'll have to tweak the CSS to change the colors.

> Note: the initial theme layout is quite sparse by design.  You will almost certainly want to add menus and sidebar widgets to enhance the layout of your site.

### Adding Users

Annotum's workflow is based around the `Articles` custom post type and a few custom user permission levels: Author, Reviewer, Editor, and Admin. 

1. `Admin` aka publication staff.  Admins can perform all functions and see all comments.  They can add and remove reviewers and coauthors, change authors, approve/reject/request revisions on articles, and publish approved articles.  Create admins by granting them the "Admin"  WordPress user level.
2. `Editor`.  Editors can do everything except publish articles (move from `review complete/approved` to `published`). Create Editors by granting them the "Editor" WordPress user level.
3. `Author`.  Authors can create articles, add and remove coauthors, and submit articles for review. Authors cannot see review comments on their articles, nor do they see who the reviewers are.  Authors can be added with either the "Contributor" or "Author" WordPress user level.  If "Author" is used, the user will be able to create regular posts and pages via the built-in WordPress functions.  Note that Co-authors are defined at the article level (not user level) when they are added by the article author. They can be any level from Contributor on up.
4. `Reviewer`. Reviewers can submit review comments and view replies to their own review comments only. Reviewers are defined at the article level when they are added by the editor. They can be any level from Contributor on up. 
5. `Subscriber`.  Essentially a site viewer, they can make comments on published articles.

> A more complete list of roles, actions, and permissions can be found [here](http://annotum.files.wordpress.com/2011/06/annotum-permissions-matrix.pdf).

### Creating Articles

Create a new article via the "new article" button from the "Articles" dashboard.  You'll see various sections for adding co-authors, including sections of your article, etc.  Note that future versions will have a greatly-enhanced set of authoring controls including the ability to import and export XML, ensure conformance with the NLM DTD, and other features such as robust handling of references.

Once you'ce created an article, you can:

1. Preview the article
2. Add Co-authors (once you do, they can also edit the article)
3. Submit the article for review

### Using Workflow 

The basic workflow is:

1. The Author creates a new article, and optionally invites co-authors to work on it.) All co-authors can post internal comments on the article during this process.
2. When ready, the Author submits the article for review via the `Submit for Review` button on the article editing screen.
3. The editor assigns reviewers, each of whom can enter review comments and a recommendation: Approve, Reject, or Request Revisions.  The editor may reply to review comments; reviewers can only see their own comments and replies to them.
4. When the reviews are in, the editor can Approve or Reject the article or Request Revsions.  Only Approved articles can be published.
5. Once an article is approved, an admin (think of admins as the publication staff) can publish it to the site.

## Additional Notes

Support.  Annotum is in a pre-release state so support is limited to the [Annotum Discussion List](https://groups.google.com/group/annotum). Please feel free to submit questions, bug reports, or suggestions there.

Background on the project, regular updates and links to related sites and information are provided on http://annotum.wordpress.com. You can also follow [@annotum](http://twitter.com/annotum) on Twitter.

## Credits

Annotum is a production of [Solvitor LLC](http://solvitor.com) with heavy lifting provided by [Crowd Favorite](http://crowdfavorite.com)

Special thanks to: Google, PLoS, NIH/NLM/NCBI, and Automattic.