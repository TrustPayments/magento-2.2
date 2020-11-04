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
use TrustPayments\Sdk\Service\PaymentConnectorService;

/**
 * Provider of payment connector information from the gateway.
 */
class PaymentConnectorProvider extends AbstractProvider
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
        parent::__construct($cache, 'trustpayments_payment_connectors',
            \TrustPayments\Sdk\Model\PaymentConnector::class);
        $this->apiClient = $apiClient;
    }

    /**
     * Gets the payment connector by the given id.
     *
     * @param int $id
     * @return \TrustPayments\Sdk\Model\PaymentConnector
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Gets a list of payment connectors.
     *
     * @return \TrustPayments\Sdk\Model\PaymentConnector[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        return $this->apiClient->getService(PaymentConnectorService::class)->all();
    }

    protected function getId($entry)
    {
        /** @var \TrustPayments\Sdk\Model\PaymentConnector $entry */
        return $entry->getId();
    }
}