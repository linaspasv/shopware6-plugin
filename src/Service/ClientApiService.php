<?php

namespace CoinGatePayment\Shopware6\Service;

use CoinGatePayment\Shopware6\CoinGatePaymentShopware6;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\PluginEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;

class ClientApiService
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var EntityRepositoryInterface
     */
    private $pluginRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $pluginRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->pluginRepository = $pluginRepository;

        \CoinGate\Client::setAppInfo('ShopWare6 Extension', $this->getPluginVersion());
    }

    private function getPluginVersion(): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('baseClass', CoinGatePaymentShopware6::class));

        $entity = $this->pluginRepository->search($criteria, Context::createDefaultContext())->first();

        return $entity instanceof PluginEntity
            ? $entity->version
            : null;
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @return \CoinGate\Client
     */
    public function get(SalesChannelContext $salesChannelContext): \CoinGate\Client
    {
        $salesChannelId = $salesChannelContext->getSalesChannelId();

        $isSandboxEnv = $this->systemConfigService->get('CoinGatePaymentShopware6.config.isLiveMode', $salesChannelId) !== true;

        $apiToken = $isSandboxEnv
            ? $this->systemConfigService->get('CoinGatePaymentShopware6.config.apiTokenForSandbox', $salesChannelId)
            : $this->systemConfigService->get('CoinGatePaymentShopware6.config.apiToken', $salesChannelId);

        return new \CoinGate\Client($apiToken, $isSandboxEnv);
    }
}