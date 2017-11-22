

#define root directory
DIR=../

#update subversion
update:
	@echo 'git update'
	rm -Rf build/
	git clone --depth=1 https://github.com/typecho/typecho.git build
	rm -Rf build/.git
	rm -Rf build/.github
	rm -f build/.gitignore
	rm -f build/.gitattributes
	for i in `find build/ -name '*.css'`; do echo $$i && java -Xmx32m -jar yuicompressor-2.4.2.jar $$i --charset UTF-8 -o $$i; done;
	for i in `find build/admin/js/ -name '*.js'`; do echo $$i && java -Xmx32m -jar yuicompressor-2.4.2.jar $$i --charset UTF-8 -o $$i; done;
	for i in `find build/ -name '*.php'`; do php -l $$i; done;


rm:
	rm -Rf build/tools/
	rm -Rf build/.git*
	rm -f build/changelog.txt
	rm -f build/.editorconfig
	rm -f build/.travis.yml
	rm -f build/README.md
	rm -Rf build/admin/scss
	rm -Rf build/admin/img/editor
	rm -Rf build/admin/img/icons
	rm -Rf build/admin/img/icons-2x
	


package:
	@echo 'package'
	make rm
	mkdir build/usr/uploads/
	chmod 777 build/usr/uploads/
	tar -cvvzf build.tar.gz build/


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
	cp build/install.php ${DIR}
	make clear


langs:
	rm -rf ../usr/langs
	mkdir ../usr/langs
	git clone https://github.com/typecho/languages.git
	#for i in `find . -name '*.po'`; do echo $$i && php msgfmt.php $$i; done;
	cd languages/ && for i in `find . -name '*.po'`; do echo `basename $$i` && msgfmt -o `basename $$i .po`.mo `basename $$i`; done;
	cp languages/*.mo ../usr/langs/
	rm -rf languages


theme:
	make update
	rm -Rf ${DIR}/usr/themes/default/
	cp -Rf build/usr/themes/default/ ${DIR}/usr/themes/
	make clear


install:
	make update
	make rm
	mkdir build/usr/uploads/
	chmod 777 build/usr/uploads/
	cp -Rf build/* ${DIR}
	make clear


pot:
	cd ../ && php tools/list.php ./ > tools/files.txt	
	cd ../ && xgettext --files-from=tools/files.txt -Lphp --from-code=UTF-8 --keyword=_t --keyword=_e --keyword=_n:1,2 --no-location --copyright-holder=Typecho --package-name=Typecho --package-version=`grep -E "VERSION = '(.*)'" ./var/Typecho/Common.php | cut -d "'" -f 2` --no-wrap --output=tools/messages.pot	
	rm -f files.txt


test:
	for i in `find ../var/ -name '*.php'`; do php -l $$i || exit 1; done;
	for i in `find ../usr/ -name '*.php'`; do php -l $$i || exit 1; done;
	for i in `find ../admin/ -name '*.php'`; do php -l $$i || exit 1; done;


all:
	make update
	make package
	make clear
