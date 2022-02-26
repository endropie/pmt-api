<?php

namespace App\Extensions\Microservice;

class Provider
{
    protected $service;
    protected $app;

    protected $name;
    protected $host;
    protected $prefix;

    protected $headers = [
        'accept', 'accept-language', 'accept-encoding', 'accept-length', 'content-type'
    ];


    public function __construct($name, $service, $option = [])
    {
        $this->app = $service->app;
        $this->service = $service;

        $this->name = $name;
        $this->host = $option["host"] ?? null;
        $this->prefix = $option["prefix"] ?? '';

        if ($host = env('MS_HOST_'. strtoupper($name), null)) $this->host = $host;
        if ($prefix = env('MS_PREFIX_'. strtoupper($name), null)) $this->prefix = $prefix;
    }

    public function handle($callback = false)
    {

        $pathInfo = $this->app['request']->getPathInfo();
        $method = $this->app['request']->method();

        if ($callback) $callback($this);

        
        $client = $this->client();
        $url  = $this->requestURI();
        $data = $this->app['request']->all();

        $this->app['router']->addRoute($method, $pathInfo, function () use ($client, $method, $url, $data) {
            try {
                // dd($data, $url);
                $response = $client->{strtolower($method)}($url, $data);

                return response($response->getBody(), $response->getStatusCode(), $response->headers());

            } catch (\Throw $t) {
                $info = env('APP_DEBUG', false) ? " [" . $t->getMessage() . ']' : '';
                return abort(504, "END POINT [SERVER] TIMEOUT." . $info);
            }
        });
    }

    protected function requestURI()
    {
        $prefixRouter = $this->service->prefix;
        $prefixRouter =  strlen($prefixRouter) && !str_starts_with($prefixRouter, '/') ? "/$prefixRouter" : $prefixRouter;

        $prefixProvider = $this->prefix();
        $prefixProvider = strlen($prefixProvider) && !str_starts_with($prefixProvider, '/') ? "/$prefixProvider" : $prefixProvider;

        $path = $prefixProvider . str_ireplace("$prefixRouter/$this->name", '', request()->getPathInfo());

        return $this->host() . $path;
    }

    public function prefix()
    {
        return $this->prefix;
    }
    
    public function host()
    {
        if (!$this->host) 
        {
            throw new \Exception('Host Microservice[' . $this->name . '] undefined.');
        }

        return $this->host;
    }

    protected function client()
    {
        $headers = collect($this->headers)
            ->mapWithKeys(function ($item) {
                if (!$this->app->request->header($item)) return[];
                return [$item =>  $this->app->request->header($item)];
            })->toArray();

        $client = $this->app['http']->withHeaders($headers);
        
        if ($token = $this->app['request']->bearerToken()) $client->withToken($token);

        return $client;
    }
}
