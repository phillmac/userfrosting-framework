<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UniformResourceLocator
 * @license   https://github.com/userfrosting/UniformResourceLocator/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\UniformResourceLocator;

/**
 * ResourcePath Class
 *
 * [Description]
 *
 * @author    Louis Charette
 */
class ResourcePath
{
    /**
     * @var string The path scheme
     */
    protected $scheme;

    /**
     * @var string The base path
     */
    protected $path;

    /**
     * @var bool Is the path shared? If yes, it won't be affected by locations
     */
    protected $shared;

    /**
     * Constructor
     *
     * @param string  $scheme
     * @param string  $path
     * @param bool    $shared
     */
    public function __construct($scheme, $path = null, $shared = false)
    {
        if (is_null($path)) {
            $path = $scheme;
        }

        $this->setScheme($scheme);
        $this->setPath($path);
        $this->setShared($shared);

        // Register the path as a stream wrapper
        $this->setupStreamWrapper();
    }

    protected function setupStreamWrapper()
    {

    }

    protected function unsetStreamWrapper()
    {

    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     *
     * @return static
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path (default null)
     *
     * @return static
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return bool
     */
    public function getShared()
    {
        return $this->shared;
    }

    /**
     * @param bool $shared
     *
     * @return static
     */
    public function setShared($shared)
    {
        $this->shared = $shared;
        return $this;
    }
}