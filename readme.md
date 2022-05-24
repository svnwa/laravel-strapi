# Laravel [Strapi](https://strapi.io/)

[![Latest Version on Packagist][ico-version]][link-packagist] [![Total Downloads][ico-downloads]][link-downloads]

This package provides an implementation to consume the [Strapi REST API](https://docs.strapi.io/developer-docs/latest/developer-resources/database-apis-reference/rest-api.html). 

The current implementation only supports fetching data from Strapi since I personally didn't have a use case to write data to it via API yet.

Currently supports Strapi v4 which is a bit different from the preceding v3 REST API.

---

## Installation

##### 1. Installation via Composer

``` bash
composer require svnwa/laravel-strapi
```

##### 2. Publishing the the config file:
``` bash
php artisan vendor:publish --provider="svnwa\laravel-strapi\LaravelStrapiServiceProvider" --tag="laravel-strapi"
```

##### 3. Editing the the **.env** file:

The ```STRAPI_CACHE_STORAGE_TIME``` is optional.[^1] 
###### The default value is defined in the config file at 3600 seconds (1 hour). 
[^1]: Time is in seconds.
``` 
STRAPI_API_TOKEN=some_strapi_api_token
STRAPI_BASE_URL="https://somestrapiurl.com"
STRAPI_CACHE_STORAGE_TIME=3600
```

##### 4. Optional "cache reset" route
This package also provides an optional route to automatically reset the cache with a Webhook from within Strapi e.g. after update or creation of a resource.
The route can be activated in the config file. Just set the value to true:
```
'cacheResetRoute' => true
``` 

---

## Usage
This package attemps to make use of a fluent api similar to Laravels Eloquent:
```php
use Svnwa\LaravelStrapi\Facades\Strapi;

$listOfRestaurants = Strapi::collection('restaurants')
    ->paginate(0,20,true)
    ->fields(['name','adress','tel'])
    ->sortBy([
            ['name','asc'],
        ]
    ->filterBy([
            ['[name][$contains]','pizza'],
        ])
    ->populate()
    ->locale('de')
    ->withFullUrls()
    ->publicationState('preview')
    ->get();
```
#### Examples

1. [Get a list of entries](https://github.com/svnwa/laravel-strapi#get-a-list-of-entries)
2. [Get an entry](https://github.com/svnwa/laravel-strapi#get-a-list-of-entries)
3. [Parameters](https://github.com/svnwa/laravel-strapi#using-strapis-basic-api-parameters)

    + [sort](https://github.com/svnwa/laravel-strapi#sort-sorting-the-response)
    + [pagination](https://github.com/svnwa/laravel-strapi#pagination-page-through-entries)
    + [filters](https://github.com/svnwa/laravel-strapi#filters)
    + [fields](https://github.com/svnwa/laravel-strapi#fields)
    + [populate](https://github.com/svnwa/laravel-strapi#populate)
    + [publicationState](https://github.com/svnwa/laravel-strapi#publicationstate)
    + [locale](https://github.com/svnwa/laravel-strapi#locale)


* ###### Get a list of entries

```GET``` ```api/restaurants```

```php
use Svnwa\LaravelStrapi\Facades\Strapi;

$listOfRestaurants = Strapi::collection('restaurants')->get();
```


* ###### Get an entry

```GET``` ```api/restaurants/999```
```php
use Svnwa\LaravelStrapi\Facades\Strapi;

$listOfRestaurants = Strapi::entry('restaurants',999)->get();
```

#### Using Strapis basic [API Parameters](https://docs.strapi.io/developer-docs/latest/developer-resources/database-apis-reference/rest-api.html#api-parameters):

* ###### ```sort``` Sorting the response
Supports Strapis sorting on one or multiple fields and/or their direction

```GET``` ```api/restaurants?sort[0]=id:asc&sort[1]=name:desc```
```php
use Svnwa\LaravelStrapi\Facades\Strapi;

$listOfRestaurants = Strapi::collection('restaurants')
    ->sortBy([
        ['id','asc'],
        ['name','desc'],
    ]);
```
* ###### ```pagination``` Page through entries
Supports Strapis pagination on results by offset

```GET``` ```api/restaurants?pagination[start]=0&pagination[limit]=20&pagination[withCount]=true```
```php
use Svnwa\LaravelStrapi\Facades\Strapi;

$listOfRestaurants = Strapi::collection('restaurants')
    ->paginate(0,20,true);
```
* ###### ```filters```
Supports Strapis filter results found with its "Get entries" method

```GET``` ```api/restaurants?filters[name][$contains]=pizza```
```php
use Svnwa\LaravelStrapi\Facades\Strapi;

$listOfRestaurants = Strapi::collection('restaurants')
    ->filterBy([
        ['[name][$contains]','pizza'],
    ]);
```
* ###### ```fields```
Queries can accept a fields parameter to select only some fields.

```GET``` ```api/restaurants?fields[0]=name&fields[1]=adress&fields[2]=tel```
```php
use Svnwa\LaravelStrapi\Facades\Strapi;

$listOfRestaurants = Strapi::collection('restaurants')
    ->fields(['name','adress','tel']);
```
* ###### ```populate```
Queries can accept a populate parameter to populate various field types

Populate with wildcard. If no parameter is given to the populate() method it defauls to using the wildcard operator:
```GET``` ```api/restaurants?populate=*```
```php
use Svnwa\LaravelStrapi\Facades\Strapi;

$listOfRestaurants = Strapi::collection('restaurants')
    ->populate();
```
or:

```GET``` ```api/restaurants?populate[0]=menu&populate[1]=customers```
```php
use Svnwa\LaravelStrapi\Facades\Strapi;

$listOfRestaurants = Strapi::collection('restaurants')
    ->populate(['menu','customers']);
```
Tip: Strapi offers deep population for e.g. nested components and does not populate them by default. Even not with the wildcard *
```GET``` ```api/restaurants?populate[0]=menu.images```

* ###### ```publicationState```
```GET``` ```api/restaurants?publicationState=preview```
Queries can accept a publicationState parameter to fetch entries based on their publication state:

```php
use Svnwa\LaravelStrapi\Facades\Strapi;

$listOfRestaurants = Strapi::collection('restaurants')
    ->publicationState('preview');
```

* ###### ```locale```
The locale API parameter can be used to get entries from a specific locale

```GET``` ```api/restaurants?locale=de```
```php
use Svnwa\LaravelStrapi\Facades\Strapi;

$listOfRestaurants = Strapi::collection('restaurants')
    ->locale('de');
```

## License

MIT. Please see the [license file](LICENCE) for more information.

[ico-version]: https://img.shields.io/packagist/v/svnwa/laravel-strapi.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/svnwa/laravel-strapi.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/svnwa/laravel-strapi
[link-downloads]: https://packagist.org/packages/svnwa/laravel-strapi
[link-author]: https://github.com/svnwa
