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
namespace TrustPayments\Payment\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for Trust Payments transaction info search results.
 *
 * @api
 */
interface TransactionInfoSearchResultsInterface extends SearchResultsInterface
{

    /**
     * Get transaction infos list.
     *
     * @return \TrustPayments\Payment\Api\Data\TransactionInfoInterface[]
     */
    public function getItems();

    /**
     * Set transaction infos list.
     *
     * @param \TrustPayments\Payment\Api\Data\TransactionInfoInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}