<?php declare(strict_types=1);

namespace Torq\Shopware\Common\Command;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Shopware\Core\Maintenance\MaintenanceException;
use Symfony\Component\Console\Input\InputInterface;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;

class SalesChannelCreateCommand extends Command
{
    public function __construct(
        private readonly DefinitionInstanceRegistry $definitionRegistry,
        private readonly EntityRepository $salesChannelRepository,
        private readonly EntityRepository $paymentMethodRepository,
        private readonly EntityRepository $shippingMethodRepository,
        private readonly EntityRepository $countryRepository,
        private readonly EntityRepository $categoryRepository,
        private readonly EntityRepository $snippetSetRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('torq:sales-channel:create')
            ->setDescription('Create a sales Channel')
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Id for the sales channel',Uuid::randomHex())
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name for the application')
            ->addOption('languageId', null, InputOption::VALUE_REQUIRED, 'Default language', Defaults::LANGUAGE_SYSTEM)
            ->addOption('currencyId', null, InputOption::VALUE_REQUIRED, 'Default currency', Defaults::CURRENCY)
            ->addOption('paymentMethodId', null, InputOption::VALUE_REQUIRED, 'Default payment method')
            ->addOption('shippingMethodId', null, InputOption::VALUE_REQUIRED, 'Default shipping method')
            ->addOption('countryIso', null, InputOption::VALUE_REQUIRED, 'Default country iso')
            ->addOption('typeId', null, InputOption::VALUE_OPTIONAL, 'Sales channel type id')
            ->addOption('customerGroupId', null, InputOption::VALUE_REQUIRED, 'Default customer group')
            ->addOption('navigationCategoryId', null, InputOption::VALUE_REQUIRED, 'Default Navigation Category')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'App URL for storefront')
            ->addOption('snippetSetId', null, InputOption::VALUE_REQUIRED, 'Default snippet set')
            ->addOption('isoCode', null, InputOption::VALUE_REQUIRED, 'Snippet set iso code')
        ;
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //TODO - make sure it doesn't exist?  or do an upsert?
        
        $context = Context::createDefaultContext();

        $id = $input->getOption('id');
        $name = $input->getOption('name');
        $typeId = $input->getOption('typeId') ?? Defaults::SALES_CHANNEL_TYPE_STOREFRONT;
        $languageId = $input->getOption('languageId') ?? Defaults::LANGUAGE_SYSTEM;
        $currencyId = $input->getOption('currencyId') ?? Defaults::CURRENCY;
        $paymentMethodId = $input->getOption('paymentMethodId') ?? $this->getFirstActivePaymentMethodId($context);
        $shippingMethodId = $input->getOption('shippingMethodId') ?? $this->getFirstActiveShippingMethodId($context);
        // use the country iso to get an id
        $countryId = $input->getOption('countryIso') ? $this->getCountryIdByIso($input->getOption('countryIso'), $context) : null;
        if(!isset($countryId)){
            $countryId = $this->getFirstActiveCountryId($context);
        }

        $currencies = [];
        $languages = [];
        $shippingMethods = [];
        $paymentMethods = [];
        $countries = [];
        $currencies = $this->formatToMany($currencies, $currencyId, 'currency', $context);
        $languages = $this->formatToMany($languages, $languageId, 'language', $context);
        $shippingMethods = $this->formatToMany($shippingMethods, $shippingMethodId, 'shipping_method', $context);
        $paymentMethods = $this->formatToMany($paymentMethods, $paymentMethodId, 'payment_method', $context);
        $countries = $this->formatToMany($countries, $countryId, 'country', $context);

        $data = [
            'id' => $id,
            'name' => $name,
            'typeId' => $typeId,
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),

            // default selection
            'languageId' => $languageId,
            'currencyId' => $currencyId,
            'paymentMethodId' => $paymentMethodId,
            'shippingMethodId' => $shippingMethodId,
            'countryId' => $countryId,
            'customerGroupId' => $customerGroupId ?? $this->getCustomerGroupId($context),
            'navigationCategoryId' => $navigationCategoryId ?? $this->getRootCategoryId($context),

            // available mappings
            'currencies' => $currencies,
            'languages' => $languages,
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods,
            'countries' => $countries,
        ];

        $overwrites = $this->getSalesChannelConfiguration($input, $output);
        $data = array_replace_recursive($data, $overwrites);

        $this->salesChannelRepository->create([$data], $context);

        return self::SUCCESS; 
    }

    private function getFirstActiveShippingMethodId(Context $context): string
    {
        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true));

        $shippingMethodId = $this->shippingMethodRepository->searchIds($criteria, $context)->firstId();
        if (!\is_string($shippingMethodId)) {
            throw MaintenanceException::couldNotGetId('first active shipping method');
        }

        return $shippingMethodId;
    }

    private function getFirstActivePaymentMethodId(Context $context): string
    {
        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('position'));

        $paymentMethodId = $this->paymentMethodRepository->searchIds($criteria, $context)->firstId();
        if (!\is_string($paymentMethodId)) {
            throw MaintenanceException::couldNotGetId('first active payment method');
        }

        return $paymentMethodId;
    }

    private function getCountryIdByIso($countryIso, $context): string
    {
        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true))
            ->addFilter(new EqualsFilter('iso', $countryIso));

        $countryId = $this->countryRepository->searchIds($criteria, $context)->firstId();
        if (!\is_string($countryId)) {
            throw MaintenanceException::couldNotGetId('country by iso');
        }

        return $countryId;
    }


    private function getFirstActiveCountryId(Context $context): string
    {
        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('position'));

        $countryId = $this->countryRepository->searchIds($criteria, $context)->firstId();
        if (!\is_string($countryId)) {
            throw MaintenanceException::couldNotGetId('first active country');
        }

        return $countryId;
    }

    private function getRootCategoryId(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('category.parentId', null));
        $criteria->addSorting(new FieldSorting('category.createdAt', FieldSorting::ASCENDING));

        $categoryId = $this->categoryRepository->searchIds($criteria, $context)->firstId();
        if (!\is_string($categoryId)) {
            throw MaintenanceException::couldNotGetId('root category');
        }

        return $categoryId;
    }

    /**
     * @return array<array{id: string}>
     */
    private function getAllIdsOf(string $entity, Context $context): array
    {
        /** @var array<string> $ids */
        $ids = $this->definitionRegistry->getRepository($entity)->searchIds(new Criteria(), $context)->getIds();

        return array_map(
            static fn (string $id): array => ['id' => $id],
            $ids
        );
    }

    private function getCustomerGroupId(Context $context): string
    {
        $criteria = (new Criteria())
            ->setLimit(1);

        $repository = $this->definitionRegistry->getRepository(CustomerGroupDefinition::ENTITY_NAME);

        $id = $repository->searchIds($criteria, $context)->firstId();

        if ($id === null) {
            throw MaintenanceException::couldNotGetId('customer group');
        }

        return $id;
    }

    /**
     * @param list<string>|null $values
     *
     * @return array<array{id: string}>
     */
    private function formatToMany(?array $values, string $default, string $entity, Context $context): array
    {
        if (!\is_array($values)) {
            return $this->getAllIdsOf($entity, $context);
        }

        $values = array_unique(array_merge($values, [$default]));

        return array_map(
            static fn (string $id): array => ['id' => $id],
            $values
        );
    }

    protected function getSalesChannelConfiguration(InputInterface $input, OutputInterface $output): array
    {
        $snippetSet = $input->getOption('snippetSetId') ?? $this->guessSnippetSetId($input->getOption('isoCode'));

        return [
            'domains' => [
                [
                    'url' => $input->getOption('url'),
                    'languageId' => $input->getOption('languageId'),
                    'snippetSetId' => $snippetSet,
                    'currencyId' => $input->getOption('currencyId'),
                ],
            ],
            'navigationCategoryDepth' => 3,
        ];
    }
    private function guessSnippetSetId(?string $isoCode = null): string
    {
        $snippetSet = $this->getSnippetSetId($isoCode);

        if ($snippetSet === null) {
            $snippetSet = $this->getSnippetSetId();
        }

        if ($snippetSet === null) {
            throw new \InvalidArgumentException(\sprintf('Snippet set with isoCode %s cannot be found.', $isoCode));
        }

        return $snippetSet;
    }

    private function getSnippetSetId(?string $isoCode = null): ?string
    {
        $isoCode = $isoCode ?: 'en-GB';
        $isoCode = str_replace('_', '-', $isoCode);
        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('iso', $isoCode));

        return $this->snippetSetRepository->searchIds($criteria, Context::createCLIContext())->firstId();
    }

}
