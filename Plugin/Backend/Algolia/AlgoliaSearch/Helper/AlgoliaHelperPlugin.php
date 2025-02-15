<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Develo\Typesense\Plugin\Backend\Algolia\AlgoliaSearch\Helper;

use Algolia\AlgoliaSearch\Helper\AlgoliaHelper;
use Develo\Typesense\Adapter\Client;
use Develo\Typesense\Services\ConfigService;
use Develo\Typesense\Model\Config\Source\TypeSenseIndexMethod;

class AlgoliaHelperPlugin
{
    /**
     * @var ConfigService
     */
    protected ConfigService $configService;

    /**
     * @var Client $typesenseClient
     */
    protected Client $typesenseClient;

    /**
     * @param ConfigService $configService
     * @param Client $client
     */
    public function __construct(
        ConfigService $configService,
        Client        $client
    )
    {
        $this->configService = $configService;
        $this->typesenseClient = $client;
    }

    /**
     * Indexes data if config is set todo, will index into algolia or typesense or both
     */
    public function aroundSaveObjects(
        \Algolia\AlgoliaSearch\Helper\AlgoliaHelper $subject,
        \Closure $proceed,
        $indexName,
        $objects,
        $isPartialUpdate
    )
    {
        if ($this->configService->isEnabled()) {
            $result = [];
            $indexMethod = $this->configService->getIndexMethod();
            switch ($indexMethod) {
                case TypeSenseIndexMethod::METHOD_ALGOLIA:
                    $result = $proceed();
                    break;
                case TypeSenseIndexMethod::METHOD_BOTH:
                    $this->typesenseClient->addData($indexName, $objects);
                    $result = $proceed();
                    break;
                case TypeSenseIndexMethod::METHOD_TYPESENSE:
                default:
                    $this->typesenseClient->addData($indexName, $objects);
                    break;
            }
        } else {
            $result = $proceed();
        }
        return $result;
    }

     /**
     * Indexes data if config is set todo, will index into algolia or typesense or both
     */
    public function aroundDeleteObjects(
        \Algolia\AlgoliaSearch\Helper\AlgoliaHelper $subject,
        \Closure $proceed,
        $indexName,
        $objects
    )
    {
        if ($this->configService->isEnabled()) {
            $result = [];
            $indexMethod = $this->configService->getIndexMethod();
            switch ($indexMethod) {
                case TypeSenseIndexMethod::METHOD_ALGOLIA:
                    $result = $proceed();
                    break;
                case TypeSenseIndexMethod::METHOD_BOTH:
                    $this->typesenseClient->deleteData($indexName, $objects);
                    $result = $proceed();
                    break;
                case TypeSenseIndexMethod::METHOD_TYPESENSE:
                default:
                    $this->typesenseClient->deleteData($indexName, $objects);
                    break;
            }
        } else {
            $result = $proceed();
        }
        return $result;
    }

    /**
     * Indexes data if config is set todo, will index into algolia or typesense or both
     */
    public function aroundGetObjects(
        \Algolia\AlgoliaSearch\Helper\AlgoliaHelper $subject,
        \Closure $proceed,
        $indexName,
        $objects
    )
    {
        if ($this->configService->isEnabled()) {
            $result = [];
            $indexMethod = $this->configService->getIndexMethod();
            switch ($indexMethod) {
                case TypeSenseIndexMethod::METHOD_ALGOLIA:
                    $result = $proceed();
                    break;
                case TypeSenseIndexMethod::METHOD_BOTH:
                    $result = $proceed();
                    return $this->typesenseClient->getData($indexName, $objects);
                    break;
                case TypeSenseIndexMethod::METHOD_TYPESENSE:
                default:
                    return $this->typesenseClient->getData($indexName, $objects);
            }
        } else {
            $result = $proceed();
        }
        return $result;
    }

    public function aroundCopyQueryRules(
        \Algolia\AlgoliaSearch\Helper\AlgoliaHelper $subject,
        \Closure $proceed,
        $indexName,
        $objects
        ){
        if ($this->configService->isEnabled()) {
        $result = [];
        $indexMethod = $this->configService->getIndexMethod();
        switch ($indexMethod) {
            case TypeSenseIndexMethod::METHOD_ALGOLIA:
                $result = $proceed();
                break;
            case TypeSenseIndexMethod::METHOD_BOTH:
                $result = $proceed();
                break;
            case TypeSenseIndexMethod::METHOD_TYPESENSE:
            default:
                return true;
        }
    } else {
        $result = $proceed();
    }
    return $result; }

    public function aroundMoveIndex(
        \Algolia\AlgoliaSearch\Helper\AlgoliaHelper $subject,
        \Closure $proceed,
        $indexName,
        $objects
        ){
        if ($this->configService->isEnabled()) {
        $result = [];
        $indexMethod = $this->configService->getIndexMethod();
        switch ($indexMethod) {
            case TypeSenseIndexMethod::METHOD_ALGOLIA:
                $result = $proceed();
                break;
            case TypeSenseIndexMethod::METHOD_BOTH:
                $result = $proceed();
                break;
            case TypeSenseIndexMethod::METHOD_TYPESENSE:
            default:
                return true;
        }
    } else {
        $result = $proceed();
    }
    return $result; }

    public function aroundSetSettings(
        \Algolia\AlgoliaSearch\Helper\AlgoliaHelper $subject,
        \Closure $proceed,
        $indexName,
        $objects
        ){
        if ($this->configService->isEnabled()) {
        $result = [];
        $indexMethod = $this->configService->getIndexMethod();
        switch ($indexMethod) {
            case TypeSenseIndexMethod::METHOD_ALGOLIA:
                $result = $proceed();
                break;
            case TypeSenseIndexMethod::METHOD_BOTH:
                $result = $proceed();
                break;
            case TypeSenseIndexMethod::METHOD_TYPESENSE:
            default:
                return true;
        }
    } else {
        $result = $proceed();
    }
    return $result;
    }

    public function aroundDeleteInactiveProducts(
        \Algolia\AlgoliaSearch\Helper\AlgoliaHelper $subject,
        \Closure $proceed,
        $indexName,
        $objects
    ){
        die("here");
            if ($this->configService->isEnabled()) {
            $result = [];
            $indexMethod = $this->configService->getIndexMethod();
            switch ($indexMethod) {
                case TypeSenseIndexMethod::METHOD_ALGOLIA:
                    $result = $proceed();
                    break;
                case TypeSenseIndexMethod::METHOD_BOTH:
                    $result = $proceed();
                    break;
                case TypeSenseIndexMethod::METHOD_TYPESENSE:
                default:
                    return true;
            }
        } else {
            $result = $proceed();
        }
        return $result;
    }

}
