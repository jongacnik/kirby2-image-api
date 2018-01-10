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
 * Get Image API endpoint page method
 * $page->imgapi('image.jpg', [ 'width' => 100 ]);
 *
 * Get Image API endpoint field method
 * $page->imageField->imgapi([ 'width' => 100 ]);
 *
 * Get Image API endpoint / Image data field method
 * $page->imageField->imgapidata([ 'width' => 100 ]);
 *
 */

namespace KirbyImgApi;
use page;
use field;
use c;

class KirbyImgApi {
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
        if (!$attrs) return $image->url();
        return url() . '/' . $prefix . $image->uri() . '?' . http_build_query($attrs);
      }
    };

    field::$methods['imgapi'] = function ($field, $attrs = false) use ($prefix) {
      if ($image = $field->toFile()) {
        if (!$attrs) return $image->url();
        return url() . '/' . $prefix . $image->uri() . '?' . http_build_query($attrs);
      }
    };

    field::$methods['imgapidata'] = function ($field, $attrs = false) use ($prefix) {
      if ($image = $field->toFile()) {
        $query = $attrs ? '?' . http_build_query($attrs) : '';
        return [
          'src' => url() . '/' . $prefix . $image->uri() . $query,
          'width' => $image->width(),
          'height' => $image->height()
        ];
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
