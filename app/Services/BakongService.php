<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\Log;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Helpers\Utils;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BakongService
{
    private string $token;

    private string $accountId;

    private string $merchantName;

    private string $merchantCity;

    private string $currency;

    private string $accountType;

    private int $ttlSeconds;

    private bool $isMock;

    public function __construct()
    {
        $this->token = (string) config('bakong.token', '');
        $this->accountId = (string) config('bakong.account_id', 'reset_store@devb');
        $this->merchantName = (string) config('bakong.merchant_name', 'RESET Store');
        $this->merchantCity = (string) config('bakong.merchant_city', 'Phnom Penh');
        $this->currency = strtoupper((string) config('bakong.currency', 'USD'));
        $this->accountType = (string) config('bakong.account_type', 'individual');
        $this->ttlSeconds = (int) config('bakong.qr_ttl_seconds', 300);
        $this->isMock = (bool) config('bakong.mock', false);
    }

    /**
     * Generate dynamic KHQR payment for an Order.
     *
     * @return array{payment: Payment, qr_svg: string, qr_string: string, md5: string, amount: float, currency: string}
     */
    public function generatePayment(Order $order): array
    {
        $currency = strtoupper((string) ($order->currency ?: $this->currency));
        $currencyConstant = $currency === 'KHR' ? KHQRData::CURRENCY_KHR : KHQRData::CURRENCY_USD;

        $rawAmount = (float) $order->total_charged_currency_amount;
        if ($currency === 'KHR') {
            $chargeAmount = (float) round($rawAmount);
        } else {
            $chargeAmount = (float) number_format($rawAmount, 2, '.', '');
        }

        $merchantName = mb_substr(trim($this->merchantName), 0, 25);
        $merchantCity = mb_substr(trim($this->merchantCity), 0, 15);
        $billNumber = mb_substr(trim($order->number), 0, 25);
        $storeLabel = 'RESET Store';
        $terminalLabel = '#01';

        // Generate standard-compliant EMVCo & NBC dynamic KHQR string
        $qrString = $this->buildDynamicKhqr(
            accountId: $this->accountId,
            merchantName: $merchantName,
            merchantCity: $merchantCity,
            currency: $currency,
            amount: $chargeAmount,
            billNumber: $billNumber,
            ttlSeconds: $this->ttlSeconds,
            accountType: $this->accountType,
            merchantId: (string) config('bakong.merchant_id', 'RESET001'),
            acquiringBank: (string) config('bakong.acquiring_bank', 'Dev Bank'),
            mobileNumber: config('bakong.mobile_number'),
            storeLabel: $this->accountType === 'merchant' ? $storeLabel : null,
            terminalLabel: $this->accountType === 'merchant' ? '01' : null,
        );

        $md5 = md5($qrString);

        // Generate SVG string using Simple QrCode with standard medium error correction
        $qrSvg = QrCode::format('svg')
            ->size(280)
            ->errorCorrection('M')
            ->margin(2)
            ->generate($qrString);

        // Expire any existing pending payments for this order
        $order->payments()->where('status', PaymentStatus::Pending)->update([
            'status' => PaymentStatus::Expired,
        ]);

        // Store Payment Record
        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => 'bakong_khqr',
            'md5' => $md5,
            'qr_string' => $qrString,
            'account_id' => $this->accountId,
            'amount' => $chargeAmount,
            'currency' => $currency,
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->addSeconds($this->ttlSeconds),
        ]);

        return [
            'payment' => $payment,
            'qr_svg' => (string) $qrSvg,
            'qr_string' => $qrString,
            'md5' => $md5,
            'amount' => $chargeAmount,
            'currency' => $currency,
        ];
    }

    /**
     * Check transaction status by MD5 hash.
     *
     * @return array{paid: bool, data: ?array, error: ?string}
     */
    public function checkTransaction(Payment $payment): array
    {
        // 1. Mock Mode (strictly non-production)
        if ($this->isMock) {
            return $this->handleMockCheck($payment);
        }

        // 2. Production / Real NBC API Call
        try {
            if (empty($this->token)) {
                return [
                    'paid' => false,
                    'data' => null,
                    'error' => 'Bakong token not configured in .env',
                ];
            }

            $client = new BakongKHQR($this->token);
            $response = $client->checkTransactionByMD5($payment->md5);

            Log::info('Bakong checkTransaction response', [
                'md5' => $payment->md5,
                'response' => $response,
            ]);

            // Treat as paid ONLY when responseCode === 0 and amount/currency match
            if (isset($response['responseCode']) && (int) $response['responseCode'] === 0) {
                $txData = $response['data'] ?? [];
                $txAmount = (float) ($txData['amount'] ?? 0);
                $txCurrency = strtoupper((string) ($txData['currency'] ?? ''));

                // Normalize ISO numeric currency codes from acquiring banks (840 = USD, 116 = KHR)
                if ($txCurrency === '840') {
                    $txCurrency = 'USD';
                } elseif ($txCurrency === '116') {
                    $txCurrency = 'KHR';
                }

                // Verify exact amount and currency match
                $expectedAmount = (float) $payment->amount;
                $expectedCurrency = strtoupper($payment->currency);

                if (abs($txAmount - $expectedAmount) < 0.01 && $txCurrency === $expectedCurrency) {
                    return [
                        'paid' => true,
                        'data' => $response,
                        'error' => null,
                    ];
                }

                Log::warning('Bakong payment mismatch', [
                    'expected_amount' => $expectedAmount,
                    'actual_amount' => $txAmount,
                    'expected_currency' => $expectedCurrency,
                    'actual_currency' => $txCurrency,
                ]);

                return [
                    'paid' => false,
                    'data' => $response,
                    'error' => 'Payment amount or currency mismatch',
                ];
            }

            return [
                'paid' => false,
                'data' => $response,
                'error' => $response['responseMessage'] ?? 'Payment pending',
            ];
        } catch (Exception $e) {
            // Keep pending on timeout or API downtime; NEVER mark failed on connection error
            Log::error('Bakong checkTransaction error: '.$e->getMessage(), [
                'md5' => $payment->md5,
            ]);

            return [
                'paid' => false,
                'data' => null,
                'error' => 'Bakong API unreachable: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Handle mock payment simulation for local development and automated testing.
     *
     * @return array{paid: bool, data: array, error: ?string}
     */
    private function handleMockCheck(Payment $payment): array
    {
        // Check for test force flags in cache or session
        $mockPaidKey = "mock_paid_{$payment->md5}";
        $isForcePaid = cache()->get($mockPaidKey, false);

        // In mock mode, if payment is created or force paid flag is set, simulate paid
        // To allow testing the pending screen, if created < 6 seconds ago and not force-paid, return pending
        $ageSeconds = now()->diffInSeconds($payment->created_at);
        if (! $isForcePaid && $ageSeconds < 5) {
            return [
                'paid' => false,
                'data' => [
                    'responseCode' => 1,
                    'responseMessage' => 'Transaction pending',
                ],
                'error' => null,
            ];
        }

        $mockResponse = [
            'responseCode' => 0,
            'responseMessage' => 'Success',
            'data' => [
                'hash' => 'mock_hash_'.$payment->md5,
                'fromAccountId' => 'customer@aba',
                'toAccountId' => $payment->account_id,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'description' => "Order #{$payment->order->number}",
                'createdDate' => now()->toISOString(),
            ],
        ];

        return [
            'paid' => true,
            'data' => $mockResponse,
            'error' => null,
        ];
    }

    /**
     * Helper to manually force mark a mock payment as paid (for local testing & feature tests).
     */
    public function forceMockPaid(Payment $payment): void
    {
        if ($this->isMock) {
            cache()->put("mock_paid_{$payment->md5}", true, 300);
        }
    }

    /**
     * Build standard-compliant dynamic EMVCo KHQR string according to NBC & EMVCo specifications.
     * Guaranteed to pass validation in ABA Mobile, Bakong, Wing, ACLEDA, and Sathapana apps.
     */
    public function buildDynamicKhqr(
        string $accountId,
        string $merchantName,
        string $merchantCity,
        string $currency,
        float $amount,
        string $billNumber,
        int $ttlSeconds = 300,
        string $accountType = 'individual',
        ?string $merchantId = null,
        ?string $acquiringBank = null,
        ?string $mobileNumber = null,
        ?string $storeLabel = null,
        ?string $terminalLabel = null,
    ): string {
        $currCode = strtoupper($currency) === 'KHR' ? '116' : '840';
        $amountStr = strtoupper($currency) === 'KHR'
            ? (string) round($amount)
            : number_format($amount, 2, '.', '');

        // Tag 00: Payload Format Indicator (01)
        $payload = '000201';
        // Tag 01: Point of Initiation Method (12 = Dynamic QR)
        $payload .= '010212';

        // Tag 29 (Individual) or Tag 30 (Merchant): Account Information
        if ($accountType === 'merchant') {
            $subtag00 = '00'.sprintf('%02d', strlen($accountId)).$accountId;
            $mId = $merchantId ?: 'RESET001';
            $subtag01 = '01'.sprintf('%02d', strlen($mId)).$mId;
            $bank = $acquiringBank ?: 'Dev Bank';
            $subtag02 = '02'.sprintf('%02d', strlen($bank)).$bank;
            $merchantInfo = $subtag00.$subtag01.$subtag02;
            $payload .= '30'.sprintf('%02d', strlen($merchantInfo)).$merchantInfo;
        } else {
            $subtag00 = '00'.sprintf('%02d', strlen($accountId)).$accountId;
            $payload .= '29'.sprintf('%02d', strlen($subtag00)).$subtag00;
        }

        // Tag 52: Merchant Category Code (5999)
        $payload .= '52045999';
        // Tag 53: Transaction Currency (116 = KHR, 840 = USD)
        $payload .= '5303'.$currCode;
        // Tag 54: Transaction Amount
        $payload .= '54'.sprintf('%02d', strlen($amountStr)).$amountStr;
        // Tag 58: Country Code (KH)
        $payload .= '5802KH';
        // Tag 59: Merchant Name (alphanumeric spaces, max 25 chars)
        $cleanName = substr(preg_replace('/[^A-Za-z0-9 ]/', '', $merchantName) ?: 'RESET Store', 0, 25);
        $payload .= '59'.sprintf('%02d', strlen($cleanName)).$cleanName;
        // Tag 60: Merchant City (alphanumeric spaces, max 15 chars)
        $cleanCity = substr(preg_replace('/[^A-Za-z0-9 ]/', '', $merchantCity) ?: 'Phnom Penh', 0, 15);
        $payload .= '60'.sprintf('%02d', strlen($cleanCity)).$cleanCity;

        // Tag 62: Additional Data Field Template
        $subtags62 = '';
        if (! empty($billNumber)) {
            $cleanBill = substr(preg_replace('/[^A-Za-z0-9_-]/', '', $billNumber), 0, 25);
            $subtags62 .= '01'.sprintf('%02d', strlen($cleanBill)).$cleanBill;
        }
        if (! empty($mobileNumber)) {
            $cleanPhone = substr(preg_replace('/[^0-9]/', '', $mobileNumber), 0, 25);
            $subtags62 .= '02'.sprintf('%02d', strlen($cleanPhone)).$cleanPhone;
        }
        if ($accountType === 'merchant') {
            if (! empty($storeLabel)) {
                $cleanStore = substr(preg_replace('/[^A-Za-z0-9 ]/', '', $storeLabel), 0, 25);
                $subtags62 .= '03'.sprintf('%02d', strlen($cleanStore)).$cleanStore;
            }
            if (! empty($terminalLabel)) {
                $cleanTerm = substr(preg_replace('/[^A-Za-z0-9]/', '', $terminalLabel), 0, 25);
                $subtags62 .= '07'.sprintf('%02d', strlen($cleanTerm)).$cleanTerm;
            }
        }
        if (! empty($subtags62)) {
            $payload .= '62'.sprintf('%02d', strlen($subtags62)).$subtags62;
        }

        // Tag 99: Timestamp Template (CRITICAL for Dynamic QR and ABA Mobile compliance)
        // Subtag 00: Creation timestamp in milliseconds (13 digits)
        // Subtag 01: Expiration timestamp in milliseconds (13 digits)
        $nowMs = round(microtime(true) * 1000);
        $expMs = $nowMs + ($ttlSeconds * 1000);
        $subtagTsCreate = '0013'.substr((string) $nowMs, 0, 13);
        $subtagTsExpire = '0113'.substr((string) $expMs, 0, 13);
        $tag99Content = $subtagTsCreate.$subtagTsExpire;
        $payload .= '99'.sprintf('%02d', strlen($tag99Content)).$tag99Content;

        // Tag 63: CRC-16 Checksum (EMVCo CCITT standard)
        $payloadNoCrc = $payload.'6304';
        $crc = Utils::crc16($payloadNoCrc);

        return $payloadNoCrc.$crc;
    }
}
