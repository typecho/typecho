<?php

namespace PHPSTORM_META {
    override(\Typecho\Widget::widget(0), map([
        '' => '@'
    ]));

    exitPoint(\Typecho\Widget\Response::redirect());
    exitPoint(\Typecho\Widget\Response::throwContent());
    exitPoint(\Typecho\Widget\Response::throwFile());
    exitPoint(\Typecho\Widget\Response::throwJson());
    exitPoint(\Typecho\Widget\Response::throwXml());
    exitPoint(\Typecho\Widget\Response::goBack());

    override(\Widget\Options::__get(0), map([
        'feedUrl'               =>  string,
        'feedRssUrl'            =>  string,
        'feedAtomUrl'           =>  string,
        'commentsFeedUrl'       =>  string,
        'commentsFeedRssUrl'    =>  string,
        'commentsFeedAtomUrl'   =>  string,
        'xmlRpcUrl'             =>  string,
        'index'                 =>  string,
        'siteUrl'               =>  string,
        'routingTable'          =>  \ArrayObject::class,
        'rootUrl'               =>  string,
        'themeUrl'              =>  string,
        'pluginUrl'             =>  string,
        'adminUrl'              =>  string,
        'loginUrl'              =>  string,
        'loginAction'           =>  string,
        'registerUrl'           =>  string,
        'registerAction'        =>  string,
        'profileUrl'            =>  string,
        'logoutUrl'             =>  string,
        'serverTimezone'        =>  int,
        'contentType'           =>  string,
        'software'              =>  string,
        'version'               =>  string,
        'markdown'              =>  int,
        'allowedAttachmentTypes'=>  \ArrayObject::class
    ]));

    override(\Typecho\Widget::__get(0), map([
        'sequence' => int,
        'length'   => int
    ]));
}