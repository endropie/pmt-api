<?php

namespace App\Extensions\Microservice;

use Illuminate\Http\Request;

class Factory
{
    public $app;
    
    public $prefix = "/api";
    
    protected $providers = [];
    

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function router($name, $callback = false)
    {
        if ($callback) 
        {
            $this->providers[$name] = $callback($name, $this);

        }
        else {
            $this->providers[$name] = (new \App\Extensions\Microservice\Provider($name, $this));
        }
    }

    public function routing($callback = false)
    {
        if ($this->hasRouter()) {
            return false;
        }

        if ($provider = $this->provider()) {
            $provider->handle();
        }
        
        return false;
    }

    protected function provider()
    {
        $prefix = $this->prefix;
        $prefix = strlen($prefix) && !str_starts_with($prefix, '/') ? "/$prefix" : "$prefix";
        
        $arr = explode('/', request()->getPathInfo(), 4);

        if ($key = $arr[($prefix == "/") ? 1 : 2]) {
            if (in_array($key, array_keys($this->providers))) return $this->providers[$key];
        }
        
        return false;
    }

    public function requestPath()
    {

        $module = $this->module ?? $this->module();

        if ($module) {
            $prefix = $this->prefix;
            $prefix = strlen($prefix) && !str_starts_with($prefix, '/') ? "/$prefix" : $prefix;

            $prefixModule = env("MS_PREFIX_" . strtoupper($module), null) ?? $this->providers;
            $prefixModule = strlen($prefixModule) && !str_starts_with($prefixModule, '/') ? "/$prefixModule" : $prefixModule;

            return $prefixModule . str_ireplace("$prefix/$module", '', request()->getPathInfo());
        }

        throw new \Exception('Module Microservice[' . $module . '] undefined.');
    }

    public function hasRouter()
    {
        return (boolean) isset($this->app['router']->getRoutes()[request()->method() . request()->getPathInfo()]);
    }
}