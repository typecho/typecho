

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
	for i in `find build/ -name '*.css'`; do echo $$i && java -Xmx32m -jar yuicompressor-2.4.2.jar $$i --charset UTF-8 -o $$i; done;
	for i in `find build/admin/javascript/ -name '*.js'`; do echo $$i && java -Xmx32m -jar yuicompressor-2.4.2.jar $$i --charset UTF-8 -o $$i; done;
	for i in `find build/ -name '*.php'`; do php -l $$i; done;


package:
	@echo 'package'
	rm -Rf build/mockup/
	rm -Rf build/tools/
	rm -Rf build/todo.txt
	rm -Rf build/usr/plugins/*
	rm -Rf build/admin/scss
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
	rm -Rf ${DIR}/usr/plugins/HelloWorld/
	rm -Rf ${DIR}/usr/plugins/Akismet/
	rm -Rf ${DIR}/usr/plugins/MagikeToTypecho/
	rm -Rf ${DIR}/usr/plugins/WordpressToTypecho/
	rm -Rf ${DIR}/usr/plugins/Textile2/
	rm -Rf ${DIR}/usr/plugins/PostToQzone/
	rm -Rf ${DIR}/usr/plugins/SimpleCode.php
	rm -Rf ${DIR}/usr/plugins/PageToLinks.php
	rm -Rf ${DIR}/usr/plugins/Creole/
	rm -Rf ${DIR}/usr/plugins/TinyMCE/
	rm -Rf ${DIR}/usr/plugins/FlashMp3Player/
	rm -Rf ${DIR}/usr/plugins/ConnectToTwitter/
	rm -Rf ${DIR}/usr/plugins/ShareCode/
	rm -Rf ${DIR}/usr/plugins/ZenCoding/
	rm -Rf ${DIR}/usr/plugins/GoogleCodePrettify/
	rm -Rf ${DIR}/usr/plugins/GoogleCodeSVN/
	cp -Rf build/usr/plugins/HelloWorld/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/Akismet/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/MagikeToTypecho/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/WordpressToTypecho/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/Textile2/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/PostToQzone/ ${DIR}/usr/plugins/
	cp build/usr/plugins/SimpleCode.php ${DIR}/usr/plugins/
	cp build/usr/plugins/PageToLinks.php ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/Creole/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/TinyMCE/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/FlashMp3Player/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/ConnectToTwitter/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/ShareCode/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/ZenCoding/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/GoogleCodePrettify/ ${DIR}/usr/plugins/
	cp -Rf build/usr/plugins/GoogleCodeSVN/ ${DIR}/usr/plugins/
	make clear


theme:
	make update
	rm -Rf ${DIR}/usr/themes/default/
	cp -Rf build/usr/themes/default/ ${DIR}/usr/themes/
	make clear


typecho:
	for i in `php list.php "build/var/Typecho/"`; do php tc.php "in=$$i&out=build/var/Typecho.php"; done;
	rm -Rf build/var/Typecho/


install:
	make update
	rm -Rf build/tools/
	rm -Rf build/todo.txt
	rm -Rf ${DIR}/usr/plugins/*
	cp -Rf build/* ${DIR}
	make clear


tinymce:
	wget http://prdownloads.sourceforge.net/tinymce/tinymce_${TINYMCE}.zip?download
	unzip tinymce_${TINYMCE}.zip
	for i in `find tinymce/jscripts/ -name '*.js'`; do php mcecp.php "in=$$i&out=../usr/plugins/TinyMCE/tiny_mce/$$i"; done;
	for i in `find tinymce/jscripts/ -name '*.htm'`; do php mcecp.php "in=$$i&out=../usr/plugins/TinyMCE/tiny_mce/$$i"; done;
	rm -Rf tinymce/
	rm -Rf tinymce_${TINYMCE}.zip
	svn ci ../usr/plugins/TinyMCE/tiny_mce/ -m "upgrade tiny_mce to ${TINYMCE}"


all:
	make update
	make package
	make clear
