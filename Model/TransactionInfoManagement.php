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
namespace TrustPayments\Payment\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use TrustPayments\Payment\Api\TransactionInfoManagementInterface;
use TrustPayments\Payment\Api\TransactionInfoRepositoryInterface;
use TrustPayments\Payment\Api\Data\TransactionInfoInterface;
use TrustPayments\Payment\Helper\Data as Helper;
use TrustPayments\Sdk\Model\ChargeAttemptState;
use TrustPayments\Sdk\Model\EntityQuery;
use TrustPayments\Sdk\Model\EntityQueryFilter;
use TrustPayments\Sdk\Model\EntityQueryFilterType;
use TrustPayments\Sdk\Model\FailureReason;
use TrustPayments\Sdk\Model\Transaction;
use TrustPayments\Sdk\Model\TransactionState;
use TrustPayments\Sdk\Service\ChargeAttemptService;

/**
 * Transaction info management service.
 */
class TransactionInfoManagement implements TransactionInfoManagementInterface
{
    /**
     *
     * @var Helper
     */
    private $helper;

    /**
     *
     * @var TransactionInfoRepositoryInterface
     */
    private $transactionInfoRepository;

    /**
     *
     * @var TransactionInfoFactory
     */
    private $transactionInfoFactory;

    /**
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     *
     * @param Helper $helper
     * @param TransactionInfoRepositoryInterface $transactionInfoRepository
     * @param TransactionInfoFactory $transactionInfoFactory
     * @param ApiClient $apiClient
     */
    public function __construct(Helper $helper, TransactionInfoRepositoryInterface $transactionInfoRepository,
        TransactionInfoFactory $transactionInfoFactory, ApiClient $apiClient)
    {
        $this->helper = $helper;
        $this->transactionInfoRepository = $transactionInfoRepository;
        $this->transactionInfoFactory = $transactionInfoFactory;
        $this->apiClient = $apiClient;
    }

    public function update(Transaction $transaction, Order $order)
    {
        try {
            $info = $this->transactionInfoRepository->getByTransactionId($transaction->getLinkedSpaceId(),
                $transaction->getId());

            if ($info->getOrderId() != $order->getId() && !$info->isExternalPaymentUrl()) {
				throw new \Exception('The Trust Payments transaction info is already linked to a different order.');
            }
        } catch (NoSuchEntityException $e) {
            $info = $this->transactionInfoFactory->create();
        }
		$info = $this->setTransactionData($transaction, $info, null, $order);
        $this->transactionInfoRepository->save($info);
        return $info;
    }

	/**
	 * Update the transaction info with the success and failure URL to redirect the customer after placing the order
	 *
	 * @param Transaction $transaction
	 * @param int $orderId
	 * @param string $successUrl
	 * @param string $failureUrl
	 * @return TransactionInfoInterface|TransactionInfo
	 * @throws \Magento\Framework\Exception\CouldNotSaveException
	 */
	public function setRedirectUrls(Transaction $transaction, $orderId, $successUrl, $failureUrl)
	{
		try {
			$info = $this->transactionInfoRepository->getByTransactionId(
				$transaction->getLinkedSpaceId(),
				$transaction->getId()
			);
		} catch (NoSuchEntityException $e) {
			$info = $this->transactionInfoFactory->create();
		}

		$info = $this->setTransactionData($transaction, $info, $orderId, null, $successUrl, $failureUrl);
		$this->transactionInfoRepository->save($info);
		return $info;
	}

	/**
	 * Update the transaction info
	 *
	 * @param Transaction $transaction
	 * @param TransactionInfo $transactionInfo,
	 * @param int|null $orderId
	 * @param Order|null $order
	 * @param string|null $successUrl
	 * @param string|null $failureUrl
	 * @return TransactionInfoInterface|TransactionInfo
	 */
	private function setTransactionData(
		Transaction $transaction,
		TransactionInfo $transactionInfo,
		$orderId = null,
		Order $order = null,
		$successUrl = null,
		$failureUrl = null
	) {
		$transactionInfo->setData(TransactionInfoInterface::TRANSACTION_ID, $transaction->getId());
		$transactionInfo->setData(TransactionInfoInterface::AUTHORIZATION_AMOUNT, $transaction->getAuthorizationAmount());
		$transactionInfo->setData(TransactionInfoInterface::ORDER_ID, $order instanceof Order ? $order->getId() : $orderId);
		$transactionInfo->setData(TransactionInfoInterface::STATE, $transaction->getState());
		$transactionInfo->setData(TransactionInfoInterface::SPACE_ID, $transaction->getLinkedSpaceId());
		$transactionInfo->setData(TransactionInfoInterface::SPACE_VIEW_ID, $transaction->getSpaceViewId());
		$transactionInfo->setData(TransactionInfoInterface::LANGUAGE, $transaction->getLanguage());
		$transactionInfo->setData(TransactionInfoInterface::CURRENCY, $transaction->getCurrency());
		$transactionInfo->setData(TransactionInfoInterface::CONNECTOR_ID,
			$transaction->getPaymentConnectorConfiguration() != null
				? $transaction->getPaymentConnectorConfiguration()->getConnector() : null);
		$transactionInfo->setData(TransactionInfoInterface::PAYMENT_METHOD_ID,
			$transaction->getPaymentConnectorConfiguration() != null &&
			$transaction->getPaymentConnectorConfiguration()
				->getPaymentMethodConfiguration() != null ? $transaction->getPaymentConnectorConfiguration()
				->getPaymentMethodConfiguration()
				->getPaymentMethod() : null);
		$transactionInfo->setData(TransactionInfoInterface::LABELS, $this->getTransactionLabels($transaction));

		if (!empty($order) && $order instanceof Order) {
			$transactionInfo->setData(TransactionInfoInterface::IMAGE, $this->getPaymentMethodImage($transaction, $order));
		}

		if (!empty($successUrl) || !empty($failureUrl)) {
			$transactionInfo->setData(TransactionInfoInterface::SUCCESS_URL, $successUrl);
			$transactionInfo->setData(TransactionInfoInterface::FAILURE_URL, $failureUrl);
		}

		if ($transaction->getState() == TransactionState::FAILED || $transaction->getState() == TransactionState::DECLINE) {
			$transactionInfo->setData(TransactionInfoInterface::FAILURE_REASON,
				$transaction->getFailureReason() instanceof FailureReason ? $transaction->getFailureReason()
					->getDescription() : null);
		}

		return $transactionInfo;
	}

    /**
     * Gets an array of the transaction's labels.
     *
     * @param Transaction $transaction
     * @return string[]
     */
    private function getTransactionLabels(Transaction $transaction)
    {
        $chargeAttempt = $this->getChargeAttempt($transaction);
        if ($chargeAttempt != null) {
            $labels = [];
            foreach ($chargeAttempt->getLabels() as $label) {
                $labels[$label->getDescriptor()->getId()] = $label->getContentAsString();
            }

            return $labels;
        } else {
            return [];
        }
    }

    /**
     * Gets the successful charge attempt of the transaction.
     *
     * @param Transaction $transaction
     * @return \TrustPayments\Sdk\Model\ChargeAttempt
     */
    private function getChargeAttempt(Transaction $transaction)
    {
        $query = new EntityQuery();
        $filter = new EntityQueryFilter();
        $filter->setType(EntityQueryFilterType::_AND);
        $filter->setChildren(
            [
                $this->helper->createEntityFilter('charge.transaction.id', $transaction->getId()),
                $this->helper->createEntityFilter('state', ChargeAttemptState::SUCCESSFUL)
            ]);
        $query->setFilter($filter);
        $query->setNumberOfEntities(1);
        $result = $this->apiClient->getService(ChargeAttemptService::class)->search($transaction->getLinkedSpaceId(),
            $query);
        if ($result != null && ! empty($result)) {
            return \current($result);
        } else {
            return null;
        }
    }

    /**
     * Gets the payment method's image.
     *
     * @param Transaction $transaction
     * @param Order $order
     * @return string
     */
    private function getPaymentMethodImage(Transaction $transaction, Order $order)
    {
        if ($transaction->getPaymentConnectorConfiguration() != null &&
            $transaction->getPaymentConnectorConfiguration()->getPaymentMethodConfiguration() != null) {
            return $this->extractImagePath(
                $transaction->getPaymentConnectorConfiguration()
                    ->getPaymentMethodConfiguration()
                    ->getResolvedImageUrl());
        } else {
            return $order->getPayment()
                ->getMethodInstance()
                ->getPaymentMethodConfiguration()
                ->getImage();
        }
    }

    /**
     * Extracts the image path from the URL.
     *
     * @param string $resolvedImageUrl
     * @return string
     */
    private function extractImagePath($resolvedImageUrl)
    {
        $index = \strpos($resolvedImageUrl, 'resource/');
        return \substr($resolvedImageUrl, $index + \strlen('resource/'));
    }
}