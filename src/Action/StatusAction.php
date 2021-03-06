<?php

/**
 * This file was created by the developers from Infifni.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://infifnisoftware.ro and write us
 * an email on contact@infifnisoftware.ro.
 */

declare(strict_types=1);

namespace Infifni\SyliusEuPlatescPlugin\Action;

use Infifni\SyliusEuPlatescPlugin\Bridge\EuPlatescBridgeInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class StatusAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();

        $details = $payment->getDetails();

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        if (isset($httpRequest->query['status']) &&
            $httpRequest->query['status'] === EuPlatescBridgeInterface::CANCELLED_STATUS
        ) {
            $details['euplatesc_status'] = EuPlatescBridgeInterface::CANCELLED_STATUS;
            $request->markCanceled();

            return;
        }

        if (false === isset($details['euplatesc_status'])) {
            $request->markNew();

            return;
        }

        if (EuPlatescBridgeInterface::COMPLETED_STATUS === $details['euplatesc_status']) {
            $request->markCaptured();

            return;
        }

        if (EuPlatescBridgeInterface::CREATED_STATUS === $details['euplatesc_status']) {
            $request->markPending();

            return;
        }

        if (EuPlatescBridgeInterface::FAILED_STATUS === $details['euplatesc_status']) {
            $request->markFailed();

            return;
        }

        $request->markUnknown();
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getFirstModel() instanceof SyliusPaymentInterface
        ;
    }
}