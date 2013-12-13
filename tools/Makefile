

#define root directory
DIR=../
TINYMCE=3_2_2


#update subversion
update:
	@echo 'git update'
	rm -Rf build/
	git clone https://github.com/typecho/typecho-replica.git build
	rm -Rf build/.git
	rm -Rf build/.gitignore
	rm -Rf build/.gitattributes
	for i in `find build/ -name '*.css'`; do echo $$i && java -Xmx32m -jar yuicompressor-2.4.2.jar $$i --charset UTF-8 -o $$i; done;
	for i in `find build/admin/js/ -name '*.js'`; do echo $$i && java -Xmx32m -jar yuicompressor-2.4.2.jar $$i --charset UTF-8 -o $$i; done;
	for i in `find build/ -name '*.php'`; do php -l $$i; done;


package:
	@echo 'package'
	rm -Rf build/tools/
	rm -Rf build/todo.txt
	rm -Rf build/changelog.txt
	rm -Rf build/.travis.yml
	rm -Rf build/README.md
	rm -Rf build/admin/scss
	rm -Rf build/admin/img/editor
	rm -Rf build/admin/img/icons
	rm -Rf build/admin/img/icons-2x
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
	make clear


theme:
	make update
	rm -Rf ${DIR}/usr/themes/default/
	cp -Rf build/usr/themes/default/ ${DIR}/usr/themes/
	make clear


install:
	make update
	rm -Rf build/tools/
	rm -Rf build/todo.txt
	rm -Rf build/changelog.txt
	rm -Rf build/.travis.yml
	rm -Rf build/README.md
	rm -Rf build/admin/scss
	rm -Rf build/admin/img/editor
	rm -Rf build/admin/img/icons
	rm -Rf build/admin/img/icons-2x
	mkdir build/usr/uploads/
	chmod 777 build/usr/uploads/
	cp -Rf build/* ${DIR}
	make clear


all:
	make update
	make package
	make clear
