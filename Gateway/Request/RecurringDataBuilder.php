<?php
/**
 *
 * Adyen Payment module (https://www.adyen.com/)
 *
 * Copyright (c) 2022 Adyen BV (https://www.adyen.com/)
 * See LICENSE.txt for license details.
 *
 * Author: Adyen <magento@adyen.com>
 */

namespace Adyen\Payment\Gateway\Request;

use Adyen\Payment\Helper\PaymentMethods;
use Adyen\Payment\Helper\Requests;
use Adyen\Payment\Helper\Vault;
use Adyen\Payment\Helper\StateData;
use Adyen\Payment\Logger\AdyenLogger;
use Adyen\Payment\Model\Method\PaymentMethodInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class RecurringDataBuilder implements BuilderInterface
{
    private Requests $adyenRequestsHelper;
    private AdyenLogger $adyenLogger;
    private Vault $vaultHelper;

    public function __construct(
        Requests $adyenRequestsHelper,
        AdyenLogger $adyenLogger,
        Vault $vaultHelper,
    ) {
        $this->adyenRequestsHelper = $adyenRequestsHelper;
        $this->adyenLogger = $adyenLogger;
        $this->vaultHelper = $vaultHelper;
    }

    public function build(array $buildSubject): array
    {
        $body = [];
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObject $paymentDataObject */
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();
        $storeId = $order->getStoreId();
        $method = $payment->getMethodInstance();

        if ($method->getCode() === PaymentMethods::ADYEN_CC) {
            $body = $this->adyenRequestsHelper->buildCardRecurringData($storeId, $payment);
        } elseif ($method instanceof PaymentMethodInterface) {
            $body = $this->vaultHelper->buildPaymentMethodRecurringData($payment, $storeId);
        } elseif ($method === PaymentMethods::ADYEN_ONE_CLICK) {
            $body = $this->adyenRequestsHelper->buildAdyenTokenizedPaymentRecurringData($storeId, $payment);
        } elseif ($method !== PaymentMethods::ADYEN_PAY_BY_LINK) {
            $this->adyenLogger->addAdyenWarning(
                sprintf('Unknown payment method: %s', $payment->getMethod()),
                $this->adyenLogger->getOrderContext($order)
            );
        }

        return [
            'body' => $body
        ];
    }
}
