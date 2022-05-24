<?php

namespace Svnwa\LaravelStrapi;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LaravelStrapi
{
    private string $strapiBaseUrl;
    private string $strapiApiUrl;
    private string $strapiApiToken;
    private int $cacheStorageTime;

    private PendingRequest $request;
    private string $apiResource;
    private int|null $entryId = null;

    // parameters for the request parameters
    private array $requestParameters = [];
    private bool $paginate = false;
    private int $paginationLimit = 20;
    private int $paginationStart = 0;
    private bool $paginationWithCount = true;
    private array $sorts = [];
    private array $filters = [];
    private array $fields = [];
    private array $populates = [];
    private bool $replaceWithFullUrls = false;
    private string $publicationState = 'live';
    private string $locale = '';

    function __construct()
    {
        $this->strapiApiToken = config('strapi.apiToken');
        $this->strapiBaseUrl = config('strapi.baseUrl');
        $this->cacheStorageTime = config('strapi.cacheStorageTime');
        $this->strapiApiUrl = $this->strapiBaseUrl . '/api';
    }


    public function collection(string $resource) : LaravelStrapi
    {
        $this->request = new PendingRequest;
        $this->apiResource = $resource;

        $this->request->withToken($this->strapiApiToken);

        return $this;
    }

    public function entry(string $resource, int $id) : LaravelStrapi
    {
        $this->request = new PendingRequest;
        $this->apiResource = $resource;
        $this->entryId = $id;

        $this->request->withToken($this->strapiApiToken);

        return $this;
    }


    public function paginate(int $start = 0, int $limit = 25, bool $withCount = true) : LaravelStrapi
    {
        $this->paginate = true;
        $this->paginationStart = $start;
        $this->paginationLimit = $limit;
        $this->paginationWithCount = $withCount;

        return $this;
    }

    public function sortBy(array $sorts = [['id', 'desc']]) : LaravelStrapi
    {
        foreach ($sorts as $sort) {
            array_push($this->sorts, $sort[0] . ':' . $sort[1]);
        }
        return $this;
    }

    public function populate(array $populates = []) : LaravelStrapi
    {
        $this->populates = count($populates) == 0 ? ['*'] : $populates;
        return $this;
    }

    private function fullUrls($array) : array
    {
        $arrayAsJson = json_encode($array); // encode array to replace the url with a full url
        $replaceAsJson = json_encode($this->strapiBaseUrl."/uploads"); // encode the replacement url in the json ecoded string
        $arrayAsJson=str_replace('"url":"\/uploads','"url":"'.substr($replaceAsJson,1,strlen($replaceAsJson)-2),$arrayAsJson);
        $decoded = json_decode($arrayAsJson, true); // decode the json as an array

        if($decoded == null){
            throw new RuntimeException("Error while replacing urls with full urls");
        }
        return $decoded;
    }

    public function filterBy(array $filters) : LaravelStrapi
    {
        foreach ($filters as $filter) {

            array_push($this->filters, $filter);
        }
        return $this;
    }

    public function fields(array $fields) : LaravelStrapi
    {
        foreach ($fields as $field) {

            array_push($this->fields, $field);
        }
        return $this;
    }

    public function withFullUrls() : LaravelStrapi
    {
        $this->replaceWithFullUrls=true;
        return $this;
    }

    public function publicationState(string $publicationState) : LaravelStrapi
    {
        if (!in_array($publicationState,['live','preview'])) {
            throw new RuntimeException("Wrong value for publication state given. '".$publicationState."' has to be 'live' or 'preview' ");
        }

        $this->publicationState=$publicationState;
        return $this;
    }

    public function locale(string $locale) : LaravelStrapi
    {
        $this->locale=$locale;
        return $this;
    }

    private function makeCacheKey() : string
    {
        return serialize($this->endpoint($this->apiResource,$this->entryId)).'_'.serialize($this->requestParameters);
    }

    private function endpoint(string $resource,int $entryId=null) : string
    {
        $endpoint  = $this->strapiApiUrl . '/' . $resource;

        if ($entryId) {
            $endpoint.='/'.$entryId;
        }

        return $endpoint;
    }

    private function checkResponseForError(array $response) : void
    {
        if (isset($response["error"]) && count($response["error"]) > 0) {
            throw new RuntimeException('Strapi Error ' . $response["error"]["status"] . ' - "' . $response["error"]["name"] . '": "' . $response["error"]["message"] . '". Occurred while trying to access "' . $this->strapiApiUrl . '/' . $this->apiResource . '"');
        }
    }

    private function addRequestParameters() : void
    {
        $this->requestParameters = [];

        // add sort parameters
        for ($i = 0; $i < count($this->sorts); $i++) {
            $this->requestParameters['sort[' . $i . ']'] = $this->sorts[$i];
        }

        // add filter parameters
        for ($i = 0; $i < count($this->filters); $i++) {
            $this->requestParameters['filters'.$this->filters[$i][0]]=$this->filters[$i][1] ;
        }

        // add populate parameters
        // if only wildcard populate ('*'), don't add brackets (since Strapi won't populate then)
        if (count($this->populates)==1 && $this->populates[0]==='*') {
            $this->requestParameters['populate'] = $this->populates[0];
        }
        else{
            for ($i = 0; $i < count($this->populates); $i++) {
                $this->requestParameters['populate[' . $i . ']'] = $this->populates[$i];
            }
        }

        // add field parameters
        for ($i = 0; $i < count($this->fields); $i++) {
            $this->requestParameters['fields[' . $i . ']'] = $this->fields[$i];
        }

        // add publicationState parameter
        // only add if pubicationState is 'preview' since 'live' is Strapis default value anyway
        if ($this->publicationState == 'preview') {
            $this->requestParameters['publicationState'] = $this->publicationState;
        }

        if ($this->locale !== '') {
            $this->requestParameters['locale'] = $this->locale;
        }

        // add pagination parameters
        if ($this->paginate) {
            $this->requestParameters['pagination[page]'] = $this->paginationStart;
            $this->requestParameters['pagination[pageSize]'] = $this->paginationLimit;
            $this->requestParameters['pagination[withCount]'] = $this->paginationWithCount;
        }
    }


    public function get() : array
    {
        $this->addRequestParameters();
        // dd($this->request->get($this->endpoint($this->apiResource,$this->entryId), $this->requestParameters));

        $res = Cache::remember($this->makeCacheKey(),$this->cacheStorageTime, function() {
            $response = $this->request->get($this->endpoint($this->apiResource,$this->entryId), $this->requestParameters)->json();
            $this->checkResponseForError($response);
            return $response;
        });

        if($this->replaceWithFullUrls){
            $res = $this->fullUrls($res);
        }
        $this->resetParameters();
        return $res;
    }

    private function resetParameters(){
        $this->requestParameters = [];
        $this->paginate = false;
        $this->paginationLimit = 20;
        $this->paginationStart = 0;
        $this->paginationWithCount = true;
        $this->sorts = [];
        $this->filters = [];
        $this->fields = [];
        $this->populates = [];
        $this->replaceWithFullUrls = false;
        $this->publicationState = 'live';
        $this->locale = '';
        $this->entryId = null;
    }
}
