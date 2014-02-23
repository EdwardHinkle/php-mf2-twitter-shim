<?php

namespace p3k\twitter;

  // Call this once with your twitter credentials before using the parse function
  function client($clientID=null, $clientSecret=null, $accessToken=null, $accessTokenSecret=null) {
    static $client;
    if(isset($client)) return $client;
    if($clientID)
      $client = new \Twitter($clientID, $clientSecret, $accessToken, $accessTokenSecret);
    return $client;
  }

  function parseTweet($url) {
    $client = client();
    if($client == null) {
      throw new \Exception('Twitter client not configured. Run p3k\\twitter\\client() once with your Twitter credentials to initialize.');
    }

    $mf2 = array(
      'items' => array()
    );

    if(!preg_match('/https?:\/\/twitter\.com\/[^\/]+\/statuse?s?\/([0-9]+)/', $url, $match))
      return $mf2;

    $tweetID = $match[1];

    $response = $client->request('statuses/show/'.$tweetID, 'GET');

    if($response == null)
      return $mf2;

    if(property_exists($response, 'errors'))
      return $mf2;

    if(!property_exists($response, 'user') || !property_exists($response->user, 'screen_name'))
      return $mf2;

    if(property_exists($response->user, 'utc_offset') && $response->user->utc_offset) {
      // Dates from the Twitter API look like 
      // "Mon Jan 20 13:42:51 +0000 2014"
      // always with +0000 offset.
      // Change the offset based on the user's timezone, then adjust the date after it's created
      $h = floor($response->user->utc_offset / 60 / 60);
      $m = $response->user->utc_offset - ($h * 60 * 60);
      $offset = sprintf('%+03d:%02d', $h, $m);
      $date = new \DateTime(str_replace('+0000', $offset, $response->created_at));
      $date->modify($response->user->utc_offset.' seconds');
    } else {
      $date = new \DateTime($response->created_at);
    }

    $date = $date->format('c');

    $categories = array();
    if(preg_match_all('/(?<=\s)#([a-z0-9_]+)/i', $response->text, $matches)) {
      foreach($matches[1] as $hashtag) {
        $categories[] = $hashtag;
      }
    }

    $urlMapper = array();
    if(property_exists($response->user, 'entities') && property_exists($response->user->entities, 'url')) {
      foreach($response->user->entities->url->urls as $tcoURL) {
        $urlMapper[$tcoURL->url] = $tcoURL->expanded_url;
      }
    }

    $author = array(
      'type' => array('h-card'),
      'properties' => array(
        'name' => array($response->user->name),
        'nickname' => array($response->user->screen_name),
        'photo' => array($response->user->profile_image_url),
        'url' => array('https://twitter.com/'.$response->user->screen_name)
      )
    );

    $text = $response->text;

    // Un-shorten links in tweets
    // regex from http://daringfireball.net/2010/07/improved_regex_for_matching_urls
    if(preg_match_all('#(?i)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))#', $text, $matches)) {
      foreach($matches[0] as $link) {
        // Skip a few well-known patterns
        if(preg_match('|twitter.com/[^\s]+|', $link))
          continue;

        $final = getCanonicalURL($link);

        if($link != $final) {
          // Update the post with the actual un-shortened URL
          $text = str_replace($link, $final, $text);
          $changed = TRUE;
        }
      }
    }

    $item = array(
      'type' => array('h-entry'),
      'properties' => array(
        'author' => array($author),
        'url' => array('https://twitter.com/' . $response->user->screen_name . '/status/' . $tweetID),
        'published' => array($date),
        'content' => array(array(
          'html' => $text, // TODO: could do some autolinking here if we bring in a library like cassis
          'value' => $text
        )),
        'name' => array($text),
        'category' => $categories
      ),
    );

    if(property_exists($response, 'place') && $response->place && property_exists($response->place, 'full_name')) {
      $item['properties']['geo'] = array($response->place->full_name);
    }

    $mf2['items'][] = $item;
    $mf2['tweet'] = $response;

    return $mf2;
  }
  
  function getCanonicalURL($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
    curl_setopt($ch, CURLOPT_NOBODY, TRUE);
    curl_exec($ch);
    $resolved = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    if($resolved) 
      return $resolved;
    else 
      return $url;
  }

