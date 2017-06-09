<?php

declare(strict_types=1);

namespace Cortex\Foundation\Http\Controllers;

use Illuminate\Routing\Controller;
use Rinvex\Fort\Traits\GetsMiddleware;
use Illuminate\Foundation\Bus\DispatchesJobs;

abstract class AbstractController extends Controller
{
    use GetsMiddleware;
    use DispatchesJobs;

    /**
     * The browsing area.
     *
     * @var string
     */
    protected $brwosingArea;

    /**
     * Whitelisted methods.
     * Array of whitelisted methods which do not need to go through middleware.
     *
     * @var array
     */
    protected $middlewareWhitelist = [];

    /**
     * The broker name.
     *
     * @var string
     */
    protected $broker;

    /**
     * Create a new abstract controller instance.
     */
    public function __construct()
    {
        $this->brwosingArea = strchr(request()->route()->getName(), '.', true);
    }

    /**
     * Get the broker to be used.
     *
     * @return string
     */
    protected function getBroker()
    {
        return $this->broker;
    }
}
