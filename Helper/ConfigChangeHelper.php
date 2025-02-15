<?php

namespace Develo\Typesense\Helper;

use Algolia\AlgoliaSearch\Helper\ConfigHelper;
use Algolia\AlgoliaSearch\Helper\Data as AlgoliaHelper;
use Algolia\AlgoliaSearch\Helper\ConfigHelper as AlgoliaConfigHelper;
use Develo\Typesense\Adapter\Client;
use Develo\Typesense\Services\ConfigService;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Typesense\Client as TypeSenseClient;

class ConfigChangeHelper
{

    const INDEX_PRODUCTS = 'products';
    const INDEX_CATEGORIES = 'categories';
    const INDEX_PAGES = 'pages';

    const REQUIRED_INDEXES = [
        self::INDEX_PRODUCTS,
        self::INDEX_CATEGORIES,
        self::INDEX_PAGES
    ];

    const SORTABLE_ATTRIBUTES = ['float', 'int64', 'string'];

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Client
     */
    private $typesenseClient;

    /**
     * @var AlgoliaHelper
     */
    private $algoliaHelper;

    /**
     * @var TypeSenseClient
     */
    private $typeSenseCollecitons;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConfigService
     */
    private ConfigService $configService;

    /**
     * @var AlgoliaConfigHelper
     */
    private AlgoliaConfigHelper $algoliaConfigHelper;

    /**
     * @var AttributeRepositoryInterface
     */
    private AttributeRepositoryInterface $attributeRepository;


    /**
     * @var SearchCriteriaBuilderFactory
     */
    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;

    public function __construct(
        RequestInterface $request,
        Client $client,
        AlgoliaHelper $algoliaHelper,
        StoreManagerInterface $storeManager,
        ConfigService $configService,
        AlgoliaConfigHelper $algoliaConfigHelper,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    )
    {
        $this->request = $request;
        $this->typesenseClient = $client->getTypesenseClient();
        $this->algoliaHelper = $algoliaHelper;
        $this->typeSenseCollecitons = $this->typesenseClient->collections;
        $this->storeManager = $storeManager;
        $this->configService = $configService;
        $this->algoliaConfigHelper = $algoliaConfigHelper;
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * Creates Indexes in Typesense after credentials have been updated
     */
    public function setCollectionConfig()
    {
        if (!$this->configService->isTypeSenseEnabled()) {
            return $this;
        }

        $facets = [];

        foreach ($this->algoliaConfigHelper->getFacets() as $facet) {
            $facets[] = $facet['attribute'];
        }

        $sortingAttributes = [];

        foreach ($this->algoliaConfigHelper->getSorting() as $sorting) {
            $sortingAttributes[] = $sorting['attribute'];
        }

        $indexes = $this->getMagentoIndexes();

        $existingCollections = $this->getExistingCollections();

        foreach ($indexes as $index) {

            foreach (static::REQUIRED_INDEXES as $indexToCreate) {

                $fields = $this->getFields($facets, $sortingAttributes, $indexToCreate);

                $indexName = $index["indexName"] . "_{$indexToCreate}";

                if (isset($existingCollections[$indexName])) {
                    $this->typesenseClient->collections[$indexName]->delete();
                    unset($existingCollections[$indexName]);
                }

                $this->typeSenseCollecitons->create(
                    [
                        'name' => $indexName,
                        'enable_nested_fields' => true,
                        'fields' => $fields
                    ]
                );

            }
        }

        return $this;
    }

    /**
     * Gets existing collections from typesense
     */
    private function getExistingCollections()
    {
        $collections = $this->typeSenseCollecitons->retrieve();
        $existingCollections = [];
        foreach ($collections as $collection) {
            $existingCollections[$collection["name"]] = $collection;
        }
        return $existingCollections;
    }

    /**
     * Gets an Aloliga index name for each store
     */
    private function getMagentoIndexes()
    {
        $indexNames = [];
        foreach ($this->storeManager->getStores() as $store) {
            $indexNames[$store->getId()] = [
                'indexName' => $this->algoliaHelper->getBaseIndexName($store->getId()),
                'priceKey' => '.' . $store->getCurrentCurrencyCode($store->getId()) . '.default',
            ];
        }
        return $indexNames;
    }

    public function getFields(array $facets, array $sortingAttributes, string $index): array
    {
        switch ($index) {
            case 'products':
                $attributes = $this->algoliaConfigHelper->getProductAdditionalAttributes();
                $entityTypeCode = ProductAttributeInterface::ENTITY_TYPE_CODE;
                $defaultAttributes = [
                    ['name' => 'objectID', 'type' => 'string', 'facet' => true],
                    ['name' => 'categories', 'type' => 'object', 'facet' => true],
                    ['name' => 'visibility_search', 'type' => 'int64'],
                    ['name' => 'visibility_catalog', 'type' => 'int64', 'facet' => true],
                    [
                        'name' => 'price_default',
                        'type' => 'float',
                        'sort' => true,
                        'facet' => true
                    ],

                    [
                        'name' => 'sku',
                        'type' => 'string[]',
                        'facet' => in_array('sku', $facets),
                        'sort' => in_array('sku', $sortingAttributes)
                    ],

                    [
                        'name' => 'categories_without_path',
                        'type' => 'string[]'
                    ],

                    [
                        'name' => 'ordered_qty',
                        'type' => 'int32'
                    ],

                    [
                        'name' => 'created_at',
                        'type' => 'string'
                    ],

//                    [
//                        'name' => 'in_stock',
//                        'type' => 'int32'
//                    ],

                    [
                        'name' => 'price',
                        'type' => 'object',
                        'facet' => true
                    ],

                    [
                        'name' => 'price.AUD.default',
                        'type' => 'float',
                        'facet' => true
                    ],

                    [
                        'name' => 'image_url',
                        'type' => 'string'
                    ],

                    [
                        'name' => 'thumbnail_url',
                        'type' => 'string'
                    ],

                    [
                        'name' => 'url',
                        'type' => 'string'
                    ],

                    [
                        'name' => 'embedding',
                        'type' => 'float[]',
                        'embed' => [
                            'from' => [
                                'name',
                                'categories_without_path'
                            ],
                            'model_config' => [
                                'model_name' => 'ts/all-MiniLM-L12-v2'
                            ]
                        ]
                    ]
                ];

                // The hierarchal menu widget expects 10 levels of category.
                for ($i = 0; $i < 10; $i++) {
                    $defaultAttributes[] = [
                        'name' => 'categories.level' . $i,
                        'type' => 'string[]',
                        'facet' => true,
                        'optional' => true
                    ];
                }

                break;
            case 'categories':
                $attributes = $this->algoliaConfigHelper->getCategoryAdditionalAttributes();
                $entityTypeCode = CategoryAttributeInterface::ENTITY_TYPE_CODE;
                $defaultAttributes = [
                    ['name' => 'objectID', 'type' => 'string', 'facet' => true],
                ];
                break;
            case 'pages':
            default:
                return [
                    ['name' => 'objectID', 'type' => 'string'],
                    ['name' => 'content', 'type' => 'string'],
                    ['name' => 'slug', 'type' => 'string'],
                    ['name' => 'name', 'type' => 'string']
                ];
        }

        $attributeCodes = [];
        foreach ($attributes as $attribute) {
            if ($attribute['searchable'] === '1') {
                $attributeCodes[] = $attribute['attribute'];
            }
        }

        /** @var SearchCriteriaBuilder $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria->addFilter('attribute_code', $attributeCodes, 'IN');

        $attributeCollection = $this->attributeRepository->getList($entityTypeCode, $searchCriteria->create());

        $fields = [];

        foreach ($attributeCollection->getItems() as $attribute) {
            if (in_array($attribute->getAttributeCode(), ['price', 'sku'])) {
                continue;
            }

            $isFacet = in_array($attribute->getAttributeCode(), $facets);

            $fields[] = [
                'name' => $attribute->getAttributeCode(),
                'type' => $isFacet ? 'string[]' : 'string',
                'facet' => $isFacet,
                'sort' => in_array($attribute->getAttributeCode(), $sortingAttributes),
                'optional' => !$attribute->getIsRequired()
            ];
        }

        $fields = array_merge($fields, $defaultAttributes);

        $fields = array_unique($fields, SORT_REGULAR);

        return array_values($fields);
    }

    public function getSearchableAttributes(string $index = self::INDEX_PRODUCTS): string
    {
        $attributes = [];
        switch ($index) {
            case 'products':
                $attributes = $this->algoliaConfigHelper->getProductAdditionalAttributes();
                break;
            case 'categories':
                $attributes = $this->algoliaConfigHelper->getCategoryAdditionalAttributes();
                break;
            case 'pages':
                return 'name,slug';
        }

        $searchableAttributes = [];
        foreach ($attributes as $attribute) {
            if ($attribute['searchable'] === '1') {
                $searchableAttributes[] = $attribute['attribute'];
            }
        }

        return implode(',', $searchableAttributes);
    }
}
