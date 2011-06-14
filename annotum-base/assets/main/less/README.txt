We're using LESS.js to allow for modular CSS development, while keeping things efficient on the user's end (single CSS file).

- See https://github.com/cloudhead/less.js/
- http://lesscss.org/ gives an overview of the syntax.
- http://incident57.com/less/ is a free OSX LESS compiler that comes with all the necessary stuff bundled right in. Nice!

It's not necessary to include this directory in production code.

Note that only the main.less file should be compiled. The output path should be the css directory.

---

Files that should be compiled to CSS:
	main.less
Output directory:
	css/