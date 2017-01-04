# define constants

FROM daocloud.io/ubuntu:16.04
MAINTAINER Joyqi <magike.net@gmail.com>

ARG ubuntu_mirror=archive.ubuntu.com
ARG php_mirror=cn2
ARG php_version=7.0.14
ARG php_timezone=Asia/Shanghai
ARG nginx_version=1.10.2

# install base lib
RUN sed -i 's/archive.ubuntu.com/'$ubuntu_mirror'/g' /etc/apt/sources.list && \
    apt-get update && \
    apt-get -y upgrade && \
    apt-get install -y autoconf wget vim build-essential git libxml2-dev pkg-config && \
    apt-get install -y libxml2-dev libcurl3-dev librecode-dev libjpeg-dev libpng12-dev libsasl2-2 sasl2-bin libsasl2-2 libsasl2-dev libsasl2-modules libfreetype6-dev libreadline-dev libpcre3-dev libssl-dev libcurl4-openssl-dev

# install mysql
RUN DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server && \
#    sed -i -e "s/^bind-address\s*=\s*127.0.0.1/bind-address = 0.0.0.0/" /etc/mysql/mysql.conf.d/mysqld.cnf && \
    sed -i 's/^\(log_error\s.*\)/# \1/' /etc/mysql/my.cnf && \
    echo "mysqld_safe &" > /tmp/mysql_config && \
    echo "mysqladmin --silent --wait=30 ping || exit 1" >> /tmp/mysql_config && \
    echo "mysql -e \"GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY '123456' WITH GRANT OPTION;\"" >> /tmp/mysql_config && \
    bash /tmp/mysql_config && \
    rm -f /tmp/mysql_config

VOLUME ["/etc/mysql", "/var/lib/mysql"]

# install nginx
RUN mkdir /nginx && cd /nginx && \
    wget http://nginx.org/download/nginx-$nginx_version.tar.gz && \
    tar zxf nginx-$nginx_version.tar.gz && \
    cd nginx-$nginx_version && \
    ./configure --prefix=/nginx && make && make install && \
    cd .. && rm -rf nginx-$nginx_version.tar.gz && rm -rf nginx-$nginx_version

ADD fastcgi_params /nginx/conf/fastcgi_params.modified
ADD default_site /nginx/conf/nginx.conf
VOLUME ["/nginx/conf", "/nginx/logs"]

# install php
RUN mkdir /php && cd /php && \
    wget http://$php_mirror.php.net/distributions/php-$php_version.tar.gz && \
    tar zxf php-$php_version.tar.gz && \
    cd php-$php_version && \
    ./configure --prefix=/php --with-config-file-path=/php/etc --with-config-file-scan-dir=/php/etc/conf.d --disable-cgi --enable-mysqlnd --with-curl --with-pcre-regex --with-readline --with-recode --with-zlib --enable-fpm --with-fpm-user=www-data --with-fpm-group=www-data --with-pdo-mysql --with-gd --with-jpeg-dir=/usr --with-png-dir=/usr --enable-mbstring --enable-phar=shared --enable-gd-native-ttf --with-freetype-dir=/usr/include/freetype2 --with-openssl && \
    make && make install && cp php.ini-production /php/etc/php.ini && mv /php/etc/php-fpm.conf.default /php/etc/php-fpm.conf && mv /php/etc/php-fpm.d/www.conf.default /php/etc/php-fpm.d/www.conf && \
    echo "date.timezone="$php_timezone >> /php/etc/php.ini && \
    sed -i 's/error_reporting = /#error_reporting = /g' /php/etc/php.ini && \
    sed -i 's/display_errors = /#display_errors = /g' /php/etc/php.ini && \
    sed -i 's/pdo_mysql.default_socket\s*=/pdo_mysql.default_socket = \/var\/run\/mysqld\/mysqld.sock/g' /php/etc/php.ini && \
    echo "always_populate_raw_post_data=-1" >> /php/etc/php.ini && \
    echo "error_reporting=E_ALL" >> /php/etc/php.ini && \
    echo "display_errors=On" >> /php/etc/php.ini && \
    echo "listen.owner = www-data" >> /php/etc/php-fpm.d/www.conf && \
    echo "listen.group = www-data" >> /php/etc/php-fpm.d/www.conf && \
    sed -i 's/listen = 127.0.0.1:9000/listen = var\/run\/php-fpm.sock/g' /php/etc/php-fpm.d/www.conf

VOLUME ["/php/var/log"]

# grant all privileges to www-data
RUN usermod -u 1000 www-data

EXPOSE 80
EXPOSE 3306

CMD /php/sbin/php-fpm && /nginx/sbin/nginx && mysqld_safe
