<?php

/**
 * Template Name: RT Episode Page
 */

$context = Timber::get_context();
$post = new TimberPost();


/*

IM API Bind

- check if there is an API binding for this view
- if there is, load the binding info
- if appropriate (i.e no cache/cache expired), prepare the API call
- make the API call
- save the API response in a custom field
- field the API response to Timber

*/

$post_id = $post->ID;
$meta = get_post_meta($post_id);


/* Retrieve API response if there is one */
$apiResponseObject = Array();

if (ImmmediateAPIBind::postHasBinding($post_id, $meta)) {

  $apiResponseObject = ImmediateAPIBind::retrieveResponseObject();

}




$apiDebug = false;

if ($apiDebug) {
  print_r($meta);
}

$hasApiBinding = false;

$bind_id = 0;
$bind_field_mappings = Array();
$bind_cache_minutes = 0;
$bind_last_call = 0;
$bind_last_result = '';

if ($meta['bind_status'][0] && $meta['bind_status'][0] == 'enabled') {

  /* There's an existing binding so let's load that info */
  $hasApiBinding = true;

  $bind_id = $meta['bind_id'][0];
  $bind_field_mappings = unserialize($meta['bind_field_mappings'][0]);
  $bind_cache_minutes = $meta['bind_cache_minutes'][0];
  $bind_last_call = $meta['bind_last_call'][0];
  $bind_last_result = $meta['bind_last_result'][0];

  if (!$bind_id) {
    $bind_id = 0;
    $hasApiBinding = false;
  }

  if (!is_numeric($bind_cache_minutes)) {
    $bind_cache_minutes = 0;
  }

  if (!is_numeric($bind_last_call)) {
    $bind_last_call = 0;
  }

} else {

  /* No API bindings for this page/post */

  /* ... */

}


if ($hasApiBinding === true && ImmmediateAPIBind::checkValidBinding($bind_id) === true) {

  if ($apiDebug) {
    echo "\r\nhas valid binding";
  }


  $b = unserialize(get_option('im-api-bind-bindings'));
  $b = $b[$bind_id];

  $liveRequest = false;
  $apiResponseObject = Array();

  $reqURI = $b['baseuri'];


  /*

  if the API binding has fields, we may have to cache more than just a generic request-response

  i.e if there's a dynamic field, we would have to cache the results of the same query X times, where X is the maximum possible number of values the dynamic field could equal

  if there are no fields on the binding, it's a straight forward cache

  */
  if (count($b['reqfields'])===0) {

    /* simple cache strategy */

    if ($apiDebug) {
      echo "\r\nno fields, simple cache strategy (URI) only";
    }

    /* where no caching is set for this call */
    if ($bind_cache_minutes == 0) {

      if ($apiDebug) {
        echo "\r\ncache_minutes=0, liveRequest true";
      }

      $liveRequest = true;

    }

    /* where caching is set but it hasn't been cached yet */
    if ($liveRequest === true && $bind_last_call == 0) {

      if ($apiDebug) {
        echo "\r\ncache set but no cache last call, liveRequest true";
      }

      $liveRequest = true;

    }

    /* where caching has been set, the first call has been made, now we check to see if X minutes have passed since the last call */
    if ($liveRequest===false) {

      if ($apiDebug) {
        echo "\r\ncache enabled, first call has been made, checking time of last cache";
      }

      $diff = (time()-$bind_last_call)/60;

      if ($apiDebug) {
        echo "\r\nlast cache diff: " . $diff;
      }

      if ($diff > $bind_cache_minutes) {

        if ($apiDebug) {
          echo "\r\nhas cache but it's old, liveRequest true";
        }
        $liveRequest = true;
      }

    }

    /* if liveRequest is still false, load the cached response here */
    if ($liveRequest===false) {

      if ($apiDebug) {
        echo "\r\nall other conditions passed, now we load cached version (if it exists)";
      }

      $uriHash = hash('md5', $reqURI);

      if (get_option('cache_' . $uriHash)) {

        if ($apiDebug) {
          echo "\r\ngot option cache_" . $uriHash;
        }

        if (get_option('cachetime_' . $uriHash)) {

          if ($apiDebug) {
            echo "\r\ngot option cachetime_" . $uriHash;
          }

          $cachedTime = get_option('cachetime_' . $uriHash);
          $diff = (time()-$cachedTime)/60;

          if ($apiDebug) {
            echo "\r\ndiff between now and time of cache: " . $diff;
          }

          if ($diff > $bind_cache_minutes) {

            if ($apiDebug) {
              echo "\r\ncache has expired, liveRequest true";
            }

            $liveRequest = true;

          } else {
            $apiResponseObject = json_decode(get_option('cache_' . $uriHash));
          }

        }
      } else {

        if ($apiDebug) {
          echo "\r\nfor some reason, no cache key exists as a site option, liveRequest true";
        }

        $liveRequest = true;
      }


    }

  } else {

    /* complex cache strategy */

    if ($apiDebug) {
    echo "\r\nhas fields, complex cache strategy";
    }

    /* where the API call is via HTTP POST, we don't cache the request */
    if ($b['reqtype']=='POST') {

      if ($apiDebug) {
        echo "\r\nthis is a POST request, liveRequest true";
      }

      $liveRequest = true;

      $postVars = Array();

      foreach($bind_field_mappings as $map) {

        $reqKey = $b['reqfields'][$x]->fieldName;
        $reqVal = '';

        switch($map['bind_to']) {

          case "":
          break;

          case "Get":
            $reqVal = $_GET[$map['bind_to_opt']];
          break;

          case "Default":
            $reqVal = $b['reqfields'][$x]->defaultValue;
          break;

          case "Explicit":
            $reqVal = $map['bind_to_opt'];
          break;

        }

        $postVar = Array();
        $postVar[$reqKey] = $reqVal;

        array_push($postVars, $postVar);

      }


    }

    /* where the API call is via HTTP GET */
    if ($b['reqtype']=='GET') {

      if ($apiDebug) {
        echo "\r\nthis is a GET request";
      }

      $x = 0;
      $reqURI .= "?";

      foreach($bind_field_mappings as $map) {

        $reqKey = $b['reqfields'][$x]->fieldName;
        $reqVal = "";

        switch($map['bind_to']) {

          case "":
          break;

          case "Get":
            $reqVal = $_GET[$map['bind_to_opt']];
          break;

          case "Default":
            $reqVal = $b['reqfields'][$x]->defaultValue;
          break;

          case "Explicit":
            $reqVal = $map['bind_to_opt'];
          break;

        }

        $reqURI .= $reqKey . "=" . $reqVal;
        $x++;

        if (count($bind_field_mappings) > $x) {
          $reqURI .= "&";
        }

      }


      if ($apiDebug) {
        echo "\r\nGET request req uri: " . $reqURI;
      }

      /* Create an MD5 hash of this unique URI, as our cache key */
      $uriHash = hash('md5', $reqURI);

      if ($apiDebug) {
      echo "\r\nGET request uri hash: " . $uriHash;
      }

      /* Check to see if there is a cached version of this URI available */
      if (get_option('cache_' . $uriHash)) {

        if ($apiDebug) {
          echo "\r\nhas cache_ of GET request - cache_" . $uriHash;
        }

        if (get_option('cachetime_' . $uriHash)) {

          if ($apiDebug) {
            echo "\r\nhas cachetime_ of GET request: " . get_option('cachetime_' . $uriHash);
          }

          $cachedTime = get_option('cachetime_' . $uriHash);
          $diff = (time()-$cachedTime)/60;


          if ($apiDebug) {
            echo "\r\ndiff of time of cached GET request and now: " . $diff;
          }

          if ($diff > $bind_cache_minutes) {

            if ($apiDebug) {
              echo "\r\ncache has expired, liveRequest true";
            }

            $liveRequest = true;

          } else {
            $apiResponseObject = json_decode(get_option('cache_' . $uriHash));
          }

        } else {

          if ($apiDebug) {
            echo "\r\nno cachetime_ available, liveRequest true";
          }

          $liveRequest = true;
        }


      } else {

        if ($apiDebug) {
          echo "\r\nno cache_ available, liveRequest true";
        }
        $liveRequest = true;
      }

    }


  }


  /* Make the request, if applicable */
  if ($liveRequest===true) {

    if ($apiDebug) {
      echo "\r\n\r\nMaking a new request";
    }

    switch($b['reqtype']) {

      case "GET":

        if ($apiDebug) {
          echo "\r\nmaking GET API call to: " . $reqURI . " hash of URI: " . $uriHash;
        }

        $apiResponse = wp_remote_get($reqURI);

        if (is_wp_error($apiResponse)) {
          $error_message = $apiResponse->get_error_message();
          echo "API call error: $error_message";
        }

        if (is_array($apiResponse)) {

          $respHeaders = $apiResponse['headers'];
          $respBody = $apiResponse['body'];

          /* if caching is enabled, save the response to a site option with the hash of the URI as the key, and save another site option cachetime_KEY with the current UNIX time */
          if ($bind_cache_minutes > 0) {

            if ($apiDebug) {
              echo "\r\n\r\nsaving response to cache";
            }

            update_option('cache_' . $uriHash, $respBody);
            update_option('cachetime_' . $uriHash, time());

          }

          $apiResponseObject = json_decode($respBody);

        }


      break;

      case "POST":

        if ($apiDebug) {
          echo "\r\nmaking POST API call to: " . $reqURI;
        }

        $apiResponse = wp_remote_post($reqURI, array(
          'body'    => $postVars
        ));

        if (is_wp_error($apiResponse)) {
          $error_message = $apiResponse->get_error_message();
          echo "API call error: $error_message";
        }

        if (is_array($apiResponse)) {

          $respHeaders = $apiResponse['headers'];
          $respBody = $apiResponse['body'];

          $apiResponseObject = json_decode($respBody);

        }


      break;

    }

  }


  if ($apiDebug) {
    echo "\r\n\r\nCOMPLETE\r\n";
    print_r($apiResponseObject);
  }


}


/*  {{dump(apiResponse)}}  */





$context['post'] = $post;
$context['sitepages'] = Timber::get_posts('post_type=page&post_parent=0');

$context['apiResponse'] = (array)$apiResponseObject;

Timber::render(array('page-rt-episode.twig'), $context);

?>