<?php
/**
 * Trust Payments Magento 2
 *
 * This Magento 2 extension enables to process payments with Trust Payments (https://www.trustpayments.com//).
 *
 * @package TrustPayments_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */
namespace TrustPayments\Payment\Model\Webhook;

/**
 * Webhook listener pool interface.
 */
interface ListenerPoolInterface
{

    /**
     * Retrieves listener.
     *
     * @param string $listenerCode
     * @return ListenerInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function get($listenerCode);
}