# RapidoPress
RapidoPress is a fork of WordPress. Rapido is minimalist and is focused on the development of web portals and web applications.

**IMPORTANTS** 
- Spanish language by default. You can change it if you remove  "$wp_local_package" line in includes/init/version.php file before installation
- Assign perms to your wp-content if you can't install plugins


## RapidoPress doesn't have...

*   Multisites support
*   Links manager
*   Default contextual help tabs
*   Emojis and smilies support
*   WordPress deprecated code
*   Xmlrpc.
*   Pinback and trackback system
*   Rss support
*   Press-This
*   WordPress news widget ( dashboard )
*   Rss Widget ( dashboard )
*   Welcome Widget( dashboard )
*   Widget Calendar
*   Widget Rss
*   Widget Meta
*   Widget Links
*   Widget Archives
*   Widget Tag Cloud
*   Widget last comments
*   Widget Pages
*   theme editor and plugin editor
*   Tools menu 
*   Customizer
*   jquery-migrate and others deprecated scripts
*   Post using email
*   Password neither pages nor posts
*   Child themes support
  
## Rapido has...

*  Widget Banner ( from Image Widget plugin )
*  Widget Facebook Likebox  ( from JetPack )
*  Widget Twitter timeline ( from JetPack )
*  Widget visibility ( from JetPack )

## Better performance...

*  minify HTML and CSS  by default
*  not more unuseful attributes in css or script tags. URLs without versions and relatives to domain.
*  not more unuseful metatags: ‘wp_generator’, ‘wlwmanifest_link’, ...


## For developers...

* constant RAPIDO_PRESS always 'true'
* RapidoPress loads functions.php but it also loads  theme-functions.php ( for theme area ) or  admin-functions.php ( for admin area ) 
* lessCSS support from http://leafo.net/lessphp/
* not more "$accepted_args" in add_action or add_filter.
* add_action allow path. Examples: 

```php
 add_action(‘wp_head’, __DIR__.’/metas.html’); 
```

```php
 add_action(‘admin_init’, __DIR__.’/zonas/admin.php’);
```

* better shorcodes “$atts”. Examples

backward compatibility

```php

functionmyUrl($atts,$content=null){
  extract(shortcode_atts(array(
    "href"=>'http://'
  ),$atts));
  return'<a href="'.$href.'">'.$content.'</a>';
}
  add_shortcode("url","myUrl");

```

or 


```php
	functionmyUrl($atts,$content=null){
	  return'<a href="'.$atts['href'].'">'.$content.'</a>';
	}
	add_shortcode("url","myUrl");
```

or

```php
functionmyUrl($atts,$content=null){
 
  extract($atts(["href"=>'http://']));
 
  return'<a href="'.$href.'">'.$content.'</a>';
}
add_shortcode("url","myUrl");

```


or

```php

functionmyUrl($shortcode){
  $shortcode->defaults(["href"=>'http://']);
 
  return'<a href="'.$shortcode->href.'">'.$shortcode->getContent().'</a>';
}
add_shortcode("url","myUrl");

```



