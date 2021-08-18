<?php

declare(strict_types=1);

namespace Cortex\Foundation\Overrides\Illuminate\Routing;

use Illuminate\Routing\UrlGenerator as BaseUrlGenerator;

class UrlGenerator extends BaseUrlGenerator
{
    /**
     * Generate an absolute URL to the given path.
     *
     * @param string    $path
     * @param mixed     $extra
     * @param bool|null $secure
     *
     * @return string
     */
    public function to($path, $extra = [], $secure = null): string
    {
        if (! config('cortex.foundation.route.trailing_slash')) {
            return parent::to($path, $extra, $secure);
        }

        // First we will check if the URL is already a valid URL. If it is we will not
        // try to generate a new one but will simply return the URL as is, which is
        // convenient since developers do not always have to check if it's valid.
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $tail = implode(
            '/',
            array_map(
                'rawurlencode',
                (array) $this->formatParameters($extra)
            )
        );

        // Once we have the scheme we will compile the "tail" by collapsing the values
        // into a single string delimited by slashes. This just makes it convenient
        // for passing the array of parameters to this URL as a list of segments.
        $root = $this->formatRoot($this->formatScheme($secure));

        [$path, $query] = $this->extractQueryString($path);

        return $this->format(
            $root,
            '/'.trim($path.'/'.$tail, '/')
        ).(config('cortex.foundation.route.trailing_slash') ? '/' : '').$query;
    }

    /**
     * {@inheritdoc}
     */
    protected function routeUrl()
    {
        if (config('cortex.foundation.route.trailing_slash') && ! $this->routeGenerator) {
            $this->routeGenerator = new RouteUrlGenerator($this, $this->request);
        }

        return parent::routeUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function previous($fallback = false)
    {
        return ($previousUrl = $this->request->input('previous_url')) ? $this->to($previousUrl) : parent::previous($fallback);
    }

    /**
     * {@inheritdoc}
     */
    public function toRoute($route, $parameters, $absolute)
    {
        // Bind {locale} route parameter
        if (config('cortex.foundation.route.locale_prefix') && in_array('locale', $route->parameterNames()) && ! isset($parameters['locale'])) {
            $urlLocale = $this->request->segment(1);
            $sessionLocale = session('locale', $defaultLocale = app('laravellocalization')->getCurrentLocale());
            $parameters['locale'] = app('laravellocalization')->checkLocaleInSupportedLocales($urlLocale) ? $urlLocale
                : (app('laravellocalization')->checkLocaleInSupportedLocales($sessionLocale) ? $sessionLocale : $defaultLocale);
        }

        // Bind {central_domain} route parameter
        if (in_array('central_domain', $route->parameterNames()) && ! isset($parameters['central_domain']) && in_array($centralDomain = $this->request->getHost(), central_domains())) {
            $parameters['central_domain'] = $route->hasParameter('central_domain') ? $route->parameter('central_domain') : $centralDomain;
        }

        // Bind {tenant_domain} route parameter
        if (in_array('tenant_domain', $route->parameterNames()) && ! isset($parameters['tenant_domain']) && in_array($tenantDomain = $this->request->getHost(), tenant_domains())) {
            $parameters['tenant_domain'] = $route->hasParameter('tenant_domain') ? $route->parameter('tenant_domain') : $tenantDomain;
        }

        return $this->routeUrl()->to(
            $route,
            $this->formatParameters($parameters),
            $absolute
        );
    }
}
