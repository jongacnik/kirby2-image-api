<?php

/**
 * Kirby Image API
 *
 * resize: /imgapi/pageuri/filename.jpg?width=200
 * crop:   /imgapi/pageuri/filename.jpg?width=200&height=200&crop=1
 * 
 * Set image path in config.php:
 * c::set('imgapi.endpoint', 'img/');
 *
 * Using page:
 * $page->imgapi('image.jpg', [ 'width' => 100 ]);
 * $page->imgapidata('image.jpg', [ 'width' => 100 ]);
 *
 * Using field:
 * $page->imageField()->imgapi([ 'width' => 100 ]);
 * $page->imageField()->imgapidata([ 'width' => 100 ]);
 *
 * Using file:
 * $page->image()->imgapi([ 'width' => 100 ]);
 * $page->image()->imgapidata([ 'width' => 100 ]);
 *
 * Generate thumb by width shortcut:
 * $page->image()->imgapi(100)
 *
 * Absolute files:
 * c::set('imageapi.absolute', true);
 *
 */

namespace KirbyImgApi;
use page;
use field;
use file;
use c;

class KirbyImgApi {
  
  private static function src($image, $attrs, $prefix) {
    $attrs = KirbyImgApi::widthshortcut($attrs);
    $url = KirbyImgApi::base() . $prefix . $image->uri();
    if (!$attrs) return $url;
    return $url . '?' . http_build_query($attrs);
  }

  private static function data($image, $attrs, $prefix) {
    $attrs = KirbyImgApi::widthshortcut($attrs);
    $query = $attrs ? '?' . http_build_query($attrs) : '';
    $url = KirbyImgApi::base() . $prefix . $image->uri();
    return [
      'src' => $url . $query,
      'width' => $image->width(),
      'height' => $image->height(),
      'ratio' => $image->height() / $image->width() * 100
    ];
  }

  private static function base () {
    $base = url() === '/' ? self::filebase() . '/' : url() . '/';
    return $base;
  }

  private static function filebase () {
    if (c::get('imageapi.absolute', false)) {
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $domainName = $_SERVER['HTTP_HOST'];
      return $protocol . $domainName; 
    }
    return '';
  }

  private static function widthshortcut ($attrs) {
    return is_int($attrs) ? [ 'width' => $attrs ] : $attrs;
  }

  public static function register() {
    $prefix = c::get('imgapi.endpoint', 'imgapi/');
    
    kirby()->routes([[
      'pattern' => $prefix . '(:all?)',
      'action' => function ($uri) {
        return KirbyImgApi::handleImageRequest($uri);
      }
    ]]);

    page::$methods['imgapi'] = function($page, $filename, $attrs = false) use ($prefix) {
      if ($image = $page->image($filename)) {
        return KirbyImgApi::src($image, $attrs, $prefix);
      }
    };

    page::$methods['imgapidata'] = function($page, $filename, $attrs = false) use ($prefix) {
      if ($image = $page->image($filename)) {
        return KirbyImgApi::data($image, $attrs, $prefix);
      }
    };

    field::$methods['imgapi'] = function ($field, $attrs = false) use ($prefix) {
      if ($image = $field->toFile()) {
        return KirbyImgApi::src($image, $attrs, $prefix);
      }
    };

    field::$methods['imgapidata'] = function ($field, $attrs = false) use ($prefix) {
      if ($image = $field->toFile()) {
        return KirbyImgApi::data($image, $attrs, $prefix);
      }
    };

    file::$methods['imgapi'] = function ($image, $attrs = false) use ($prefix) {
      if ($image) {
        return KirbyImgApi::src($image, $attrs, $prefix);
      }
    };

    file::$methods['imgapidata'] = function ($image, $attrs = false) use ($prefix) {
      if ($image) {
        return KirbyImgApi::data($image, $attrs, $prefix);
      }
    };
  }

  public static function handleImageRequest($uri) {
    $uri_parts = explode('/', $uri);
    $filename = array_pop($uri_parts);
    $page = page(implode('/', $uri_parts));
    $q = kirby()->request()->query();
    if ($image = $page->image($filename)) {
      if ($q->width() || $q->height()) {
        if ($q->crop()) {
        $image->crop($q->width(), $q->height(), $q->quality())->show();
        } else {
        $image->resize($q->width(), $q->height(), $q->quality())->show();
        }
      } else {
        $image->show();
      }
    } 
  }
}

KirbyImgApi::register();
