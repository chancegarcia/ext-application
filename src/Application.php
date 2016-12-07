<?php
/**
 * teqneers/ext-application
 *
 * @category   TQ
 * @package    TQ\ExtJS\Application
 * @copyright  Copyright (C) 2015 by TEQneers GmbH & Co. KG
 */

namespace TQ\ExtJS\Application;

use TQ\ExtJS\Application\Configuration\ApplicationConfiguration;
use TQ\ExtJS\Application\Exception\FileNotFoundException;
use TQ\ExtJS\Application\Manifest\Manifest;
use TQ\ExtJS\Application\Manifest\ManifestLoaderInterface;

/**
 * Class Application
 *
 * @package TQ\ExtJS\Application
 */
class Application
{
    /**
     * @var ApplicationConfiguration
     */
    private $configuration;

    /**
     * @var ManifestLoaderInterface
     */
    protected $manifestLoader;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var string
     */
    protected $applicationId;
    
    protected $developerEnvironments = array(
        'dev',
    );

    /**
     * @param ApplicationConfiguration $configuration
     * @param ManifestLoaderInterface  $manifestLoader
     * @param string                   $environment
     */
    public function __construct(
        ApplicationConfiguration $configuration,
        ManifestLoaderInterface $manifestLoader,
        $environment = 'prod'
    ) {
        $this->configuration  = $configuration;
        $this->manifestLoader = $manifestLoader;
        $this->environment    = $environment;
    }
    
    /**
     * @return array
     */
    public function getDeveloperEnvironments()
    {
        return $this->developerEnvironments;
    }
    
    /**
     * @param array $developerEnvironments
     */
    public function setDeveloperEnvironments(array $developerEnvironments)
    {
        $this->developerEnvironments = $developerEnvironments;
    }
    
    /**
     * @return bool
     */
    public function isDevelopment()
    {
        return in_array($this->environment, $this->developerEnvironments);
    }

    /**
     * @param string|null $build
     * @return string
     */
    public function getBuildPath($build = null)
    {
        return $this->configuration->getBuildPath($build, $this->isDevelopment());
    }

    /**
     * @param string|null $build
     * @return \SplFileInfo
     */
    public function getMicroLoaderFile($build = null)
    {
        return $this->getFile($this->configuration->getMicroLoaderPath($build, $this->isDevelopment()));
    }

    /**
     * @param string|null $build
     * @return \SplFileInfo
     */
    protected function getManifestFile($build = null)
    {
        return $this->getFile($this->configuration->getManifestPath($build, $this->isDevelopment()));
    }

    /**
     * @param string $path
     * @return \SplFileInfo
     */
    protected function getFile($path)
    {
        if (!$path || !file_exists($path) || !is_readable($path)) {
            throw new FileNotFoundException($path);
        }
        return new \SplFileInfo($path);
    }

    /**
     * @param string|null $build
     * @return Manifest
     */
    public function getManifest($build = null)
    {
        return $this->manifestLoader->loadManifest(
            $this->getManifestFile($build),
            $build ?: $this->getDefaultBuild(),
            $this->isDevelopment()
        );
    }

    /**
     * @param string|null $build
     * @return \SplFileInfo
     */
    public function getAppCacheFile($build = null)
    {
        return $this->getFile($this->configuration->getAppCachePath($build, $this->isDevelopment()));
    }

    /**
     * @param string|null $build
     * @return bool
     */
    public function hasAppCache($build = null)
    {
        $appCache = $this->configuration->getAppCachePath($build, $this->isDevelopment());
        return $appCache ? true : false;
    }

    /**
     * @param string      $path
     * @param string|null $build
     * @return \SplFileInfo
     */
    public function getBuildArtifact($path, $build = null)
    {
        return $this->getFile($this->configuration->getBuildArtifactPath($path, $build, $this->isDevelopment()));
    }

    /**
     * @return string|null
     */
    public function getDefaultBuild()
    {
        return $this->configuration->getDefaultBuild();
    }

    /**
     * @return string
     */
    public function getApplicationId()
    {
        if (!$this->applicationId) {
            $manifestFile = $this->getManifestFile();
            $manifest     = @json_decode(file_get_contents($manifestFile->getPathname()), true);
            if (is_array($manifest) && isset($manifest['id'])) {
                $this->applicationId = $manifest['id'];
            } else {
                throw new \BadMethodCallException(
                    'Cannot read application id from manifest at "' . $manifestFile->getPathname() . '"'
                );
            }
        }
        return $this->applicationId;
    }
}
