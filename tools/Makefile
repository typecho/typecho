

#define root directory
DIR=../

#update subversion
update:
	@echo 'git update'
	rm -Rf build/
	git clone https://github.com/typecho/typecho.git build
	rm -Rf build/.git
	rm -f build/.gitignore
	rm -f build/.gitattributes
	for i in `find build/ -name '*.css'`; do echo $$i && java -Xmx32m -jar yuicompressor-2.4.2.jar $$i --charset UTF-8 -o $$i; done;
	for i in `find build/admin/js/ -name '*.js'`; do echo $$i && java -Xmx32m -jar yuicompressor-2.4.2.jar $$i --charset UTF-8 -o $$i; done;
	for i in `find build/ -name '*.php'`; do php -l $$i; done;


package:
	@echo 'package'
	rm -Rf build/tools/
	rm -f build/todo.txt
	rm -f build/changelog.txt
	rm -f build/.travis.yml
	rm -f build/README.md
	rm -Rf build/admin/scss
	rm -Rf build/admin/img/editor
	rm -Rf build/admin/img/icons
	rm -Rf build/admin/img/icons-2x
	rm -Rf build/usr/langs/*
	mkdir build/usr/uploads/
	chmod 777 build/usr/uploads/
	tar -cvvzf build.tar.gz build/


commonmark-php:
	rm -rf ../var/CommonMark
	git clone git@github.com:colinodell/commonmark-php.git commonmark
	php transfer.php commonmark/src CommonMark ColinODell/CommonMark
	rm -f commonmark/src/CommonMarkConverter.php
	mv commonmark/src ../var/CommonMark
	rm -rf commonmark


commonmark-js:
	rm -rf ../admin/js/stmd.js
	git clone https://github.com/jgm/stmd.git
	cp stmd/js/stmd.js ../admin/js/
	rm -rf stmd


clear:
	rm -Rf build/


upgrade:
	make update
	rm -Rf ${DIR}/admin/
	cp -Rf build/admin/ ${DIR}
	rm -Rf ${DIR}/var/
	cp -Rf build/var/ ${DIR}
	rm -Rf ${DIR}/index.php
	cp build/index.php ${DIR}
	make clear


theme:
	make update
	rm -Rf ${DIR}/usr/themes/default/
	cp -Rf build/usr/themes/default/ ${DIR}/usr/themes/
	make clear


install:
	make update
	rm -Rf build/tools/
	rm -f build/todo.txt
	rm -f build/changelog.txt
	rm -f build/.travis.yml
	rm -f build/README.md
	rm -Rf build/admin/scss
	rm -Rf build/admin/img/editor
	rm -Rf build/admin/img/icons
	rm -Rf build/admin/img/icons-2x
	mkdir build/usr/uploads/
	chmod 777 build/usr/uploads/
	cp -Rf build/* ${DIR}
	make clear


pot:
	cd ../ && php tools/list.php ./ > tools/files.txt	
	cd ../ && xgettext --files-from=tools/files.txt -Lphp --from-code=UTF-8 --keyword=_t --keyword=_e --keyword=_n:1,2 --no-location --copyright-holder=Typecho --package-name=Typecho --package-version=`grep -E "VERSION = '(.*)'" ./var/Typecho/Common.php | cut -d "'" -f 2` --no-wrap --output=tools/messages.pot	
	rm -f files.txt


all:
	make update
	make package
	make clear
