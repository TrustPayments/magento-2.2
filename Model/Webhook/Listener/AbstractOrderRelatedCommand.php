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
namespace TrustPayments\Payment\Model\Webhook\Listener;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use TrustPayments\Sdk\Model\Transaction;

/**
 * Abstract webhook listener command for order related entites.
 */
abstract class AbstractOrderRelatedCommand implements CommandInterface
{

    /**
     * Gets the invoice linked to the given transaction.
     *
     * @param Transaction $transaction
     * @param Order $order
     * @return Invoice
     */
    protected function getInvoiceForTransaction(Transaction $transaction, Order $order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            /** @var Invoice $invoice */
            if (\strpos($invoice->getTransactionId(), $transaction->getLinkedSpaceId() . '_' . $transaction->getId()) ===
                0 && $invoice->getState() != Invoice::STATE_CANCELED) {
                $invoice->load($invoice->getId());
                return $invoice;
            }
        }
    }
}