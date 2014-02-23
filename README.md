Microformats/Twitter Shim
=========================

This module takes a tweet URL and returns a data structure as if the URL had been marked up with a proper [h-entry](http://indiewebcamp.com/h-entry).

Usage
-----

Bring in this library using composer, or just by including the `src\p3k\twitter-shim.php` file.

```json
{
  "p3k/mf2-twitter-shim": "0.1.*",
}
```

First, initialize the Twitter client with your credentials.

```php
p3k\twitter\client($twitterClientID, $twitterClientSecret, $twitterAccessToken, $twitterAccessTokenSecret);
```

Then you can call `parseTweet` with a URL to a tweet and get back a nicely formatted `h-entry`.

```php
$data = p3k\twitter\parseTweet('https://twitter.com/aaronpk/status/429757886953033728');
```

License
-------

Copyright 2014 by Aaron Parecki

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.

