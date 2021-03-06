<?php
/**
 * Trust Payments Magento 2
 *
 * This Magento 2 extension enables to process payments with Trust Payments (https://www.trustpayments.com//).
 *
 * @package TrustPayments_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */
namespace TrustPayments\Payment\Model\Provider;

use Magento\Framework\Cache\FrontendInterface;
use TrustPayments\Payment\Model\ApiClient;
use TrustPayments\Sdk\Service\PaymentMethodService;

/**
 * Provider of payment method information from the gateway.
 */
class PaymentMethodProvider extends AbstractProvider
{

    /**
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     *
     * @param FrontendInterface $cache
     * @param ApiClient $apiClient
     */
    public function __construct(FrontendInterface $cache, ApiClient $apiClient)
    {
        parent::__construct($cache, 'trustpayments_payment_methods',
            \TrustPayments\Sdk\Model\PaymentMethod::class);
        $this->apiClient = $apiClient;
    }

    /**
     * Gets the payment method by the given id.
     *
     * @param int $id
     * @return \TrustPayments\Sdk\Model\PaymentMethod
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Gets a list of payment methods.
     *
     * @return \TrustPayments\Sdk\Model\PaymentMethod[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        return $this->apiClient->getService(PaymentMethodService::class)->all();
    }

    protected function getId($entry)
    {
        /** @var \TrustPayments\Sdk\Model\PaymentMethod $entry */
        return $entry->getId();
    }
}