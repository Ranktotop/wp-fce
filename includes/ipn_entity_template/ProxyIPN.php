<?php

declare(strict_types=1);

/**
 * ----------------------------
 * HELPERS (wie in Python)
 * ----------------------------
 */
function epoch_to_dt(null|int|float|string $v): ?DateTimeImmutable
{
    if ($v === null || $v === '' || $v === 0 || $v === '0') {
        return null;
    }
    if (is_string($v) && trim($v) === '') {
        return null;
    }
    if (!is_int($v) && !is_float($v) && !is_string($v)) {
        return null;
    }
    if (is_string($v)) {
        if (!is_numeric($v)) {
            return null;
        }
        $v = (float)$v;
    }
    $sec = (float)$v;
    $dt  = (new DateTimeImmutable('@' . (string) $sec))->setTimezone(new DateTimeZone('UTC'));
    return $dt;
}

function maybe_iso_or_epoch_to_dt(mixed $v): ?DateTimeImmutable
{
    if ($v === null) return null;
    if ($v instanceof DateTimeImmutable) return $v;

    if (is_string($v)) {
        // ISO 8601, „Z“ tolerieren
        $str = str_replace('Z', '+00:00', $v);
        try {
            $dt = new DateTimeImmutable($str);
            return $dt;
        } catch (\Throwable) {
            // weiter unten epoch_try
        }
    }
    return epoch_to_dt($v);
}

/**
 * ----------------------------
 * KLEINE VALIDIER-HILFEN
 * ----------------------------
 */
function assert_required(array $a, string $key): void
{
    if (!array_key_exists($key, $a)) {
        throw new InvalidArgumentException("Missing required key: $key");
    }
}
function as_string(array $a, string $key): string
{
    assert_required($a, $key);
    return (string)$a[$key];
}
function as_bool(array $a, string $key): bool
{
    assert_required($a, $key);
    return (bool)$a[$key];
}
function as_int(array $a, string $key): int
{
    assert_required($a, $key);
    return (int)$a[$key];
}
function as_float(array $a, string $key): float
{
    assert_required($a, $key);
    return (float)$a[$key];
}
function as_array(array $a, string $key): array
{
    assert_required($a, $key);
    $v = $a[$key];
    if (!is_array($v)) throw new InvalidArgumentException("Expected array for key: $key");
    return $v;
}
function as_email(array $a, string $key): string
{
    $email = as_string($a, $key);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("Invalid email for key: $key");
    }
    return $email;
}
function as_url(array $a, string $key): string
{
    $url = as_string($a, $key);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new InvalidArgumentException("Invalid url for key: $key");
    }
    return $url;
}

/**
 * ----------------------------
 * DTOs (Modelle wie Pydantic)
 * ----------------------------
 * Jede ::fromArray($data) wirft bei fehlenden Pflichtfeldern.
 * Optional-Datetime-Felder werden normalisiert (ISO/Epoch -> DateTimeImmutable|null)
 */

final class ProxyIPNModelCustomer
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $email,          // EmailStr -> string (validiert)
        public string $company,
        public string $country,
        public string $city,
        public string $street,
        public string $street_number,
        public string $zip,
        public string $phone,
        public string $vat_id,
        public bool   $newsletter_optin,
    ) {}

    public static function fromArray(array $a): self
    {
        return new self(
            as_string($a, 'first_name'),
            as_string($a, 'last_name'),
            as_email($a, 'email'),
            as_string($a, 'company'),
            as_string($a, 'country'),
            as_string($a, 'city'),
            as_string($a, 'street'),
            as_string($a, 'street_number'),
            as_string($a, 'zip'),
            as_string($a, 'phone'),
            as_string($a, 'vat_id'),
            as_bool($a, 'newsletter_optin'),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

final class ProxyIPNModelAffiliate
{
    public function __construct(
        public float $amount_net,
        public float $amount_gross,
        public float $amount_vat,
        public float $commission_rate,
        public string $id,
        public string $notes,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            as_float($a, 'amount_net'),
            as_float($a, 'amount_gross'),
            as_float($a, 'amount_vat'),
            as_float($a, 'commission_rate'),
            as_string($a, 'id'),
            as_string($a, 'notes'),
        );
    }
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

final class ProxyIPNModelJointVenturePartners
{
    public function __construct(
        public float $amount_net,
        public float $amount_gross,
        public float $amount_vat,
        public float $commission_rate,
        public string $id,
        public string $name,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            as_float($a, 'amount_net'),
            as_float($a, 'amount_gross'),
            as_float($a, 'amount_vat'),
            as_float($a, 'commission_rate'),
            as_string($a, 'id'),
            as_string($a, 'name'),
        );
    }
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

final class ProxyIPNModelJointVenture
{
    /** @param ProxyIPNModelJointVenturePartners[] $partners */
    public function __construct(
        public float $amount_net,
        public float $amount_gross,
        public float $amount_vat,
        public float $commission_rate,
        public array $partners,
        public string $notes,
    ) {}
    public static function fromArray(array $a): self
    {
        $partnersRaw = as_array($a, 'partners');
        $partners = [];
        foreach ($partnersRaw as $p) {
            $partners[] = ProxyIPNModelJointVenturePartners::fromArray((array)$p);
        }
        return new self(
            as_float($a, 'amount_net'),
            as_float($a, 'amount_gross'),
            as_float($a, 'amount_vat'),
            as_float($a, 'commission_rate'),
            $partners,
            as_string($a, 'notes'),
        );
    }
    public function toArray(): array
    {
        return [
            'amount_net' => $this->amount_net,
            'amount_gross' => $this->amount_gross,
            'amount_vat' => $this->amount_vat,
            'commission_rate' => $this->commission_rate,
            'partners' => array_map(fn($p) => $p->toArray(), $this->partners),
            'notes' => $this->notes,
        ];
    }
}

final class ProxyIPNModelEarningsTotalEarnings
{
    public function __construct(
        public float $sales_net,
        public float $sales_gross,
        public float $sales_vat,
        public float $profit_net,
        public float $profit_gross,
        public float $profit_vat,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            as_float($a, 'sales_net'),
            as_float($a, 'sales_gross'),
            as_float($a, 'sales_vat'),
            as_float($a, 'profit_net'),
            as_float($a, 'profit_gross'),
            as_float($a, 'profit_vat'),
        );
    }
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

final class ProxyIPNModelEarningsTotalCosts
{
    public function __construct(
        public float $platform_net,
        public float $platform_gross,
        public float $platform_vat,
        public float $affiliate_net,
        public float $affiliate_gross,
        public float $affiliate_vat,
        public float $joint_venture_net,
        public float $joint_venture_vat,
        public float $joint_venture_gross,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            as_float($a, 'platform_net'),
            as_float($a, 'platform_gross'),
            as_float($a, 'platform_vat'),
            as_float($a, 'affiliate_net'),
            as_float($a, 'affiliate_gross'),
            as_float($a, 'affiliate_vat'),
            as_float($a, 'joint_venture_net'),
            as_float($a, 'joint_venture_vat'),
            as_float($a, 'joint_venture_gross'),
        );
    }
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

final class ProxyIPNModelEarningsTotal
{
    public function __construct(
        public ProxyIPNModelEarningsTotalEarnings $earnings,
        public ProxyIPNModelEarningsTotalCosts $costs,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            ProxyIPNModelEarningsTotalEarnings::fromArray(as_array($a, 'earnings')),
            ProxyIPNModelEarningsTotalCosts::fromArray(as_array($a, 'costs')),
        );
    }
    public function toArray(): array
    {
        return [
            'earnings' => $this->earnings->toArray(),
            'costs' => $this->costs->toArray(),
        ];
    }
}

final class ProxyIPNModelEarningsTransactionEarnings
{
    public function __construct(
        public float $sales_net,
        public float $sales_gross,
        public float $sales_vat,
        public float $profit_net,
        public float $profit_gross,
        public float $profit_vat,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            as_float($a, 'sales_net'),
            as_float($a, 'sales_gross'),
            as_float($a, 'sales_vat'),
            as_float($a, 'profit_net'),
            as_float($a, 'profit_gross'),
            as_float($a, 'profit_vat'),
        );
    }
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

final class ProxyIPNModelEarningsTransactionCosts
{
    public function __construct(
        public float $platform_net,
        public float $platform_gross,
        public float $platform_vat,
        public float $affiliate_net,
        public float $affiliate_gross,
        public float $affiliate_vat,
        public float $joint_venture_net,
        public float $joint_venture_vat,
        public float $joint_venture_gross,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            as_float($a, 'platform_net'),
            as_float($a, 'platform_gross'),
            as_float($a, 'platform_vat'),
            as_float($a, 'affiliate_net'),
            as_float($a, 'affiliate_gross'),
            as_float($a, 'affiliate_vat'),
            as_float($a, 'joint_venture_net'),
            as_float($a, 'joint_venture_vat'),
            as_float($a, 'joint_venture_gross'),
        );
    }
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

final class ProxyIPNModelEarningsTransaction
{
    public function __construct(
        public ProxyIPNModelEarningsTransactionEarnings $earnings,
        public ProxyIPNModelEarningsTransactionCosts $costs,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            ProxyIPNModelEarningsTransactionEarnings::fromArray(as_array($a, 'earnings')),
            ProxyIPNModelEarningsTransactionCosts::fromArray(as_array($a, 'costs')),
        );
    }
    public function toArray(): array
    {
        return [
            'earnings' => $this->earnings->toArray(),
            'costs' => $this->costs->toArray(),
        ];
    }
}

final class ProxyIPNModelEarnings
{
    public function __construct(
        public ProxyIPNModelEarningsTotal $total,
        public ProxyIPNModelEarningsTransaction $transaction,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            ProxyIPNModelEarningsTotal::fromArray(as_array($a, 'total')),
            ProxyIPNModelEarningsTransaction::fromArray(as_array($a, 'transaction')),
        );
    }
    public function toArray(): array
    {
        return [
            'total' => $this->total->toArray(),
            'transaction' => $this->transaction->toArray(),
        ];
    }
}

final class ProxyIPNModelTracking
{
    public function __construct(
        public string $campaign,
        public string $medium,
        public string $source,
        public string $content,
        public string $term,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            as_string($a, 'campaign'),
            as_string($a, 'medium'),
            as_string($a, 'source'),
            as_string($a, 'content'),
            as_string($a, 'term'),
        );
    }
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

final class ProxyIPNModelProduct
{
    public function __construct(
        public string $name,
        public float $single_net,
        public float $single_gross,
        public float $single_vat,
        public int $quantity,
        public float $total_net,
        public float $total_gross,
        public float $total_vat,
        public string $id,
        public string $tags,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            as_string($a, 'name'),
            as_float($a, 'single_net'),
            as_float($a, 'single_gross'),
            as_float($a, 'single_vat'),
            as_int($a, 'quantity'),
            as_float($a, 'total_net'),
            as_float($a, 'total_gross'),
            as_float($a, 'total_vat'),
            as_string($a, 'id'),
            as_string($a, 'tags'),
        );
    }
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

final class ProxyIPNModelLicense
{
    public function __construct(
        public string $key,
        public ?DateTimeImmutable $created_at,
        public string $login_url,
        public string $password,
        public string $username,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            as_string($a, 'key'),
            maybe_iso_or_epoch_to_dt($a['created_at'] ?? null),
            as_string($a, 'login_url'),
            as_string($a, 'password'),
            as_string($a, 'username'),
        );
    }
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'created_at' => $this->created_at?->format(DATE_ATOM),
            'login_url' => $this->login_url,
            'password' => $this->password,
            'username' => $this->username,
        ];
    }
}

final class ProxyIPNModelVendor
{
    public function __construct(
        public string $name,
        public string $id,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            as_string($a, 'name'),
            as_string($a, 'id'),
        );
    }
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

final class ProxyIPNModelPaymentHistoryEntry
{
    public function __construct(
        public ?DateTimeImmutable $date,
        public float $amount,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            maybe_iso_or_epoch_to_dt($a['date'] ?? null),
            as_float($a, 'amount'),
        );
    }
    public function toArray(): array
    {
        return [
            'date' => $this->date?->format(DATE_ATOM),
            'amount' => $this->amount,
        ];
    }
}

final class ProxyIPNModelPaymentHistory
{
    /** @param ProxyIPNModelPaymentHistoryEntry[] $past
     *  @param ProxyIPNModelPaymentHistoryEntry[] $upcoming
     */
    public function __construct(
        public array $past,
        public array $upcoming,
    ) {}
    public static function fromArray(array $a): self
    {
        $past = [];
        foreach (as_array($a, 'past') as $row) {
            $past[] = ProxyIPNModelPaymentHistoryEntry::fromArray((array)$row);
        }
        $upcoming = [];
        foreach (as_array($a, 'upcoming') as $row) {
            $upcoming[] = ProxyIPNModelPaymentHistoryEntry::fromArray((array)$row);
        }
        return new self($past, $upcoming);
    }
    public function toArray(): array
    {
        return [
            'past' => array_map(fn($e) => $e->toArray(), $this->past),
            'upcoming' => array_map(fn($e) => $e->toArray(), $this->upcoming),
        ];
    }
}

final class ProxyIPNModelTransaction
{
    public function __construct(
        public bool $is_topup,
        public bool $is_drop,
        public bool $is_cancellation,
        public bool $is_otp,
        public bool $is_installment,
        public bool $is_subscription,
        public bool $is_trial,
        public bool $is_test,
        public bool $is_info,
        public string $order_id,
        public ?DateTimeImmutable $order_date,
        public string $transaction_id,
        public ?DateTimeImmutable $transaction_date,
        public ?DateTimeImmutable $paid_until,
        public ProxyIPNModelPaymentHistory $payment_history_net,
        public string $invoice_url,
        public string $management_url,
        public string $currency,
        public int $current_number_of_payment,
        public int $overall_number_of_payments,
        public int $days_between_first_and_second_payment,
        public int $days_between_second_and_nth_payment,
    ) {}
    public static function fromArray(array $a): self
    {
        $invoice = as_url($a, 'invoice_url');
        $mgmt    = as_url($a, 'management_url');
        return new self(
            as_bool($a, 'is_topup'),
            as_bool($a, 'is_drop'),
            as_bool($a, 'is_cancellation'),
            as_bool($a, 'is_otp'),
            as_bool($a, 'is_installment'),
            as_bool($a, 'is_subscription'),
            as_bool($a, 'is_trial'),
            as_bool($a, 'is_test'),
            as_bool($a, 'is_info'),
            as_string($a, 'order_id'),
            maybe_iso_or_epoch_to_dt($a['order_date'] ?? null),
            as_string($a, 'transaction_id'),
            maybe_iso_or_epoch_to_dt($a['transaction_date'] ?? null),
            maybe_iso_or_epoch_to_dt($a['paid_until'] ?? null),
            ProxyIPNModelPaymentHistory::fromArray(as_array($a, 'payment_history_net')),
            $invoice,
            $mgmt,
            as_string($a, 'currency'),
            as_int($a, 'current_number_of_payment'),
            as_int($a, 'overall_number_of_payments'),
            as_int($a, 'days_between_first_and_second_payment'),
            as_int($a, 'days_between_second_and_nth_payment'),
        );
    }
    public function toArray(): array
    {
        return [
            'is_topup' => $this->is_topup,
            'is_drop' => $this->is_drop,
            'is_cancellation' => $this->is_cancellation,
            'is_otp' => $this->is_otp,
            'is_installment' => $this->is_installment,
            'is_subscription' => $this->is_subscription,
            'is_trial' => $this->is_trial,
            'is_test' => $this->is_test,
            'is_info' => $this->is_info,
            'order_id' => $this->order_id,
            'order_date' => $this->order_date?->format(DATE_ATOM),
            'transaction_id' => $this->transaction_id,
            'transaction_date' => $this->transaction_date?->format(DATE_ATOM),
            'paid_until' => $this->paid_until?->format(DATE_ATOM),
            'payment_history_net' => $this->payment_history_net->toArray(),
            'invoice_url' => $this->invoice_url,
            'management_url' => $this->management_url,
            'currency' => $this->currency,
            'current_number_of_payment' => $this->current_number_of_payment,
            'overall_number_of_payments' => $this->overall_number_of_payments,
            'days_between_first_and_second_payment' => $this->days_between_first_and_second_payment,
            'days_between_second_and_nth_payment' => $this->days_between_second_and_nth_payment,
        ];
    }
}

final class ProxyIPNModelVoucher
{
    public function __construct(
        public string $code,
        public float $amount_net,
        public float $amount_gross,
        public float $amount_vat,
    ) {}
    public static function fromArray(array $a): self
    {
        return new self(
            as_string($a, 'code'),
            as_float($a, 'amount_net'),
            as_float($a, 'amount_gross'),
            as_float($a, 'amount_vat'),
        );
    }
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

class ProxyIPNModelBase
{
    public function __construct(
        public ProxyIPNModelCustomer $customer,
        public ProxyIPNModelAffiliate $affiliate,
        public ProxyIPNModelJointVenture $joint_venture,
        public ProxyIPNModelEarnings $earnings,
        public ProxyIPNModelTracking $tracking,
        public ProxyIPNModelProduct $product,
        public ProxyIPNModelLicense $license,
        public ProxyIPNModelVendor $vendor,
        public ProxyIPNModelTransaction $transaction,
        public ProxyIPNModelVoucher $voucher,
        public string $source,
    ) {}

    public static function fromArray(array $a): self
    {
        return new self(
            ProxyIPNModelCustomer::fromArray(as_array($a, 'customer')),
            ProxyIPNModelAffiliate::fromArray(as_array($a, 'affiliate')),
            ProxyIPNModelJointVenture::fromArray(as_array($a, 'joint_venture')),
            ProxyIPNModelEarnings::fromArray(as_array($a, 'earnings')),
            ProxyIPNModelTracking::fromArray(as_array($a, 'tracking')),
            ProxyIPNModelProduct::fromArray(as_array($a, 'product')),
            ProxyIPNModelLicense::fromArray(as_array($a, 'license')),
            ProxyIPNModelVendor::fromArray(as_array($a, 'vendor')),
            ProxyIPNModelTransaction::fromArray(as_array($a, 'transaction')),
            ProxyIPNModelVoucher::fromArray(as_array($a, 'voucher')),
            as_string($a, 'source'),
        );
    }

    public function toArray(): array
    {
        return [
            'customer' => $this->customer->toArray(),
            'affiliate' => $this->affiliate->toArray(),
            'joint_venture' => $this->joint_venture->toArray(),
            'earnings' => $this->earnings->toArray(),
            'tracking' => $this->tracking->toArray(),
            'product' => $this->product->toArray(),
            'license' => $this->license->toArray(),
            'vendor' => $this->vendor->toArray(),
            'transaction' => $this->transaction->toArray(),
            'voucher' => $this->voucher->toArray(),
            'source' => $this->source,
        ];
    }
}

class ProxyIPNModelCreateRequest extends ProxyIPNModelBase
{
    public static function fromArray(array $a): self
    {
        $base = ProxyIPNModelBase::fromArray($a);
        return new self(
            $base->customer,
            $base->affiliate,
            $base->joint_venture,
            $base->earnings,
            $base->tracking,
            $base->product,
            $base->license,
            $base->vendor,
            $base->transaction,
            $base->voucher,
            $base->source
        );
    }
}

/**
 * --------------------------------------------------
 * ProxyIPNClass – imperative Klasse mit den Gettern
 * --------------------------------------------------
 * Nimmt array ODER bereits validiertes Model entgegen.
 */
final class ProxyIPNClass
{
    private array $raw_data;

    /** flache Arrays wie in deiner Python-Klasse */
    private array $customer;
    private array $affiliate;
    private array $joint_venture;
    private array $earnings;
    private array $tracking;
    private array $product;
    private array $license;
    private array $vendor;
    private array $transaction;
    private array $voucher;
    private string $source;

    public function __construct(array|ProxyIPNModelCreateRequest $data)
    {
        if (is_array($data)) {
            $model = ProxyIPNModelCreateRequest::fromArray($data);
        } elseif ($data instanceof ProxyIPNModelCreateRequest) {
            $model = $data;
        } else {
            throw new InvalidArgumentException('Expected array or ProxyIPNModelCreateRequest');
        }

        // raw_data als array
        $this->raw_data = $model->toArray();

        // flach (wie Python) für 1:1 Getter
        $this->customer    = $this->raw_data['customer'];
        $this->affiliate   = $this->raw_data['affiliate'];
        $this->joint_venture = $this->raw_data['joint_venture'];
        $this->earnings    = $this->raw_data['earnings'];
        $this->tracking    = $this->raw_data['tracking'];
        $this->product     = $this->raw_data['product'];
        $this->license     = $this->raw_data['license'];
        $this->vendor      = $this->raw_data['vendor'];
        $this->transaction = $this->raw_data['transaction'];
        $this->voucher     = $this->raw_data['voucher'];
        $this->source      = (string)$this->raw_data['source'];
    }

    // -------- CUSTOMER
    public function get_customer_first_name(): string
    {
        return $this->customer['first_name'];
    }
    public function get_customer_last_name(): string
    {
        return $this->customer['last_name'];
    }
    public function get_customer_email(): string
    {
        return (string)$this->customer['email'];
    }
    public function get_customer_company(): string
    {
        return $this->customer['company'];
    }
    public function get_customer_country(): string
    {
        return $this->customer['country'];
    }
    public function get_customer_city(): string
    {
        return $this->customer['city'];
    }
    public function get_customer_street(): string
    {
        return $this->customer['street'];
    }
    public function get_customer_street_number(): string
    {
        return $this->customer['street_number'];
    }
    public function get_customer_zip(): string
    {
        return $this->customer['zip'];
    }
    public function get_customer_phone(): string
    {
        return $this->customer['phone'];
    }
    public function get_customer_vat_id(): string
    {
        return $this->customer['vat_id'];
    }
    public function get_customer_newsletter_optin(): bool
    {
        return (bool)$this->customer['newsletter_optin'];
    }
    public function get_customer_full_name(): string
    {
        return trim($this->get_customer_first_name() . ' ' . $this->get_customer_last_name());
    }

    // -------- AFFILIATE
    public function get_affiliate_amount_net(): float
    {
        return (float)$this->affiliate['amount_net'];
    }
    public function get_affiliate_amount_gross(): float
    {
        return (float)$this->affiliate['amount_gross'];
    }
    public function get_affiliate_amount_vat(): float
    {
        return (float)$this->affiliate['amount_vat'];
    }
    public function get_affiliate_commission_rate(): float
    {
        return (float)$this->affiliate['commission_rate'];
    }
    public function get_affiliate_id(): string
    {
        return $this->affiliate['id'];
    }
    public function get_affiliate_notes(): string
    {
        return $this->affiliate['notes'];
    }

    // -------- JOINT VENTURE
    /** @return array<int, array<string,mixed>> */
    public function get_joint_venture_partners(): array
    {
        return $this->joint_venture['partners'];
    }
    public function get_joint_venture_amount_net(): float
    {
        return (float)$this->joint_venture['amount_net'];
    }
    public function get_joint_venture_amount_gross(): float
    {
        return (float)$this->joint_venture['amount_gross'];
    }
    public function get_joint_venture_amount_vat(): float
    {
        return (float)$this->joint_venture['amount_vat'];
    }
    public function get_joint_venture_commission_rate(): float
    {
        return (float)$this->joint_venture['commission_rate'];
    }
    public function get_joint_venture_notes(): string
    {
        return $this->joint_venture['notes'];
    }

    // -------- EARNINGS (total/transaction)
    public function get_total_sales_net(): float
    {
        return (float)$this->earnings['total']['earnings']['sales_net'];
    }
    public function get_total_sales_gross(): float
    {
        return (float)$this->earnings['total']['earnings']['sales_gross'];
    }
    public function get_total_sales_vat(): float
    {
        return (float)$this->earnings['total']['earnings']['sales_vat'];
    }
    public function get_total_profit_net(): float
    {
        return (float)$this->earnings['total']['earnings']['profit_net'];
    }
    public function get_total_profit_gross(): float
    {
        return (float)$this->earnings['total']['earnings']['profit_gross'];
    }
    public function get_total_profit_vat(): float
    {
        return (float)$this->earnings['total']['earnings']['profit_vat'];
    }

    public function get_total_platform_net(): float
    {
        return (float)$this->earnings['total']['costs']['platform_net'];
    }
    public function get_total_platform_gross(): float
    {
        return (float)$this->earnings['total']['costs']['platform_gross'];
    }
    public function get_total_platform_vat(): float
    {
        return (float)$this->earnings['total']['costs']['platform_vat'];
    }
    public function get_total_affiliate_net(): float
    {
        return (float)$this->earnings['total']['costs']['affiliate_net'];
    }
    public function get_total_affiliate_gross(): float
    {
        return (float)$this->earnings['total']['costs']['affiliate_gross'];
    }
    public function get_total_affiliate_vat(): float
    {
        return (float)$this->earnings['total']['costs']['affiliate_vat'];
    }
    public function get_total_joint_venture_net(): float
    {
        return (float)$this->earnings['total']['costs']['joint_venture_net'];
    }
    public function get_total_joint_venture_vat(): float
    {
        return (float)$this->earnings['total']['costs']['joint_venture_vat'];
    }
    public function get_total_joint_venture_gross(): float
    {
        return (float)$this->earnings['total']['costs']['joint_venture_gross'];
    }

    public function get_tx_sales_net(): float
    {
        return (float)$this->earnings['transaction']['earnings']['sales_net'];
    }
    public function get_tx_sales_gross(): float
    {
        return (float)$this->earnings['transaction']['earnings']['sales_gross'];
    }
    public function get_tx_sales_vat(): float
    {
        return (float)$this->earnings['transaction']['earnings']['sales_vat'];
    }
    public function get_tx_profit_net(): float
    {
        return (float)$this->earnings['transaction']['earnings']['profit_net'];
    }
    public function get_tx_profit_gross(): float
    {
        return (float)$this->earnings['transaction']['earnings']['profit_gross'];
    }
    public function get_tx_profit_vat(): float
    {
        return (float)$this->earnings['transaction']['earnings']['profit_vat'];
    }

    public function get_tx_platform_net(): float
    {
        return (float)$this->earnings['transaction']['costs']['platform_net'];
    }
    public function get_tx_platform_gross(): float
    {
        return (float)$this->earnings['transaction']['costs']['platform_gross'];
    }
    public function get_tx_platform_vat(): float
    {
        return (float)$this->earnings['transaction']['costs']['platform_vat'];
    }
    public function get_tx_affiliate_net(): float
    {
        return (float)$this->earnings['transaction']['costs']['affiliate_net'];
    }
    public function get_tx_affiliate_gross(): float
    {
        return (float)$this->earnings['transaction']['costs']['affiliate_gross'];
    }
    public function get_tx_affiliate_vat(): float
    {
        return (float)$this->earnings['transaction']['costs']['affiliate_vat'];
    }
    public function get_tx_joint_venture_net(): float
    {
        return (float)$this->earnings['transaction']['costs']['joint_venture_net'];
    }
    public function get_tx_joint_venture_vat(): float
    {
        return (float)$this->earnings['transaction']['costs']['joint_venture_vat'];
    }
    public function get_tx_joint_venture_gross(): float
    {
        return (float)$this->earnings['transaction']['costs']['joint_venture_gross'];
    }

    // -------- TRACKING
    public function get_tracking_campaign(): string
    {
        return $this->tracking['campaign'];
    }
    public function get_tracking_medium(): string
    {
        return $this->tracking['medium'];
    }
    public function get_tracking_source(): string
    {
        return $this->tracking['source'];
    }
    public function get_tracking_content(): string
    {
        return $this->tracking['content'];
    }
    public function get_tracking_term(): string
    {
        return $this->tracking['term'];
    }

    // -------- PRODUCT
    public function get_product_name(): string
    {
        return $this->product['name'];
    }
    public function get_product_single_net(): float
    {
        return (float)$this->product['single_net'];
    }
    public function get_product_single_gross(): float
    {
        return (float)$this->product['single_gross'];
    }
    public function get_product_single_vat(): float
    {
        return (float)$this->product['single_vat'];
    }
    public function get_product_quantity(): int
    {
        return (int)$this->product['quantity'];
    }
    public function get_product_total_net(): float
    {
        return (float)$this->product['total_net'];
    }
    public function get_product_total_gross(): float
    {
        return (float)$this->product['total_gross'];
    }
    public function get_product_total_vat(): float
    {
        return (float)$this->product['total_vat'];
    }
    public function get_product_id(): string
    {
        return $this->product['id'];
    }
    public function get_product_tags(): string
    {
        return $this->product['tags'];
    }

    // -------- LICENSE
    public function get_license_key(): string
    {
        return $this->license['key'];
    }
    public function get_license_created_at(): ?DateTimeImmutable
    {
        return maybe_iso_or_epoch_to_dt($this->license['created_at'] ?? null);
    }
    public function get_license_login_url(): string
    {
        return $this->license['login_url'];
    }
    public function get_license_password(): string
    {
        return $this->license['password'];
    }
    public function get_license_username(): string
    {
        return $this->license['username'];
    }

    // -------- VENDOR
    public function get_vendor_name(): string
    {
        return $this->vendor['name'];
    }
    public function get_vendor_id(): string
    {
        return $this->vendor['id'];
    }

    // -------- TRANSACTION
    public function is_topup(): bool
    {
        return (bool)$this->transaction['is_topup'];
    }
    public function is_drop(): bool
    {
        return (bool)$this->transaction['is_drop'];
    }
    public function is_cancellation(): bool
    {
        return (bool)$this->transaction['is_cancellation'];
    }
    public function is_otp(): bool
    {
        return (bool)$this->transaction['is_otp'];
    }
    public function is_installment(): bool
    {
        return (bool)$this->transaction['is_installment'];
    }
    public function is_subscription(): bool
    {
        return (bool)$this->transaction['is_subscription'];
    }
    public function is_trial(): bool
    {
        return (bool)$this->transaction['is_trial'];
    }
    public function is_test(): bool
    {
        return (bool)$this->transaction['is_test'];
    }
    public function is_info(): bool
    {
        return (bool)$this->transaction['is_info'];
    }

    public function get_transaction_order_id(): string
    {
        return $this->transaction['order_id'];
    }
    public function get_transaction_order_date(): ?DateTimeImmutable
    {
        return maybe_iso_or_epoch_to_dt($this->transaction['order_date'] ?? null);
    }

    public function get_transaction_id(): string
    {
        return $this->transaction['transaction_id'];
    }
    public function get_transaction_date(): ?DateTimeImmutable
    {
        return maybe_iso_or_epoch_to_dt($this->transaction['transaction_date'] ?? null);
    }

    public function get_transaction_paid_until(): ?DateTimeImmutable
    {
        return maybe_iso_or_epoch_to_dt($this->transaction['paid_until'] ?? null);
    }

    /** @return array{past: list<array{date:?string,amount:float}>, upcoming: list<array{date:?string,amount:float}>} */
    public function get_transaction_payment_history_net(): array
    {
        $ph = $this->transaction['payment_history_net'];
        $norm = function (array $items): array {
            $out = [];
            foreach ($items as $it) {
                $date = maybe_iso_or_epoch_to_dt($it['date'] ?? null);
                $out[] = [
                    'date' => $date?->format(DATE_ATOM),
                    'amount' => (float)$it['amount'],
                ];
            }
            return $out;
        };
        return [
            'past' => $norm($ph['past'] ?? []),
            'upcoming' => $norm($ph['upcoming'] ?? []),
        ];
        // Hinweis: falls du hier echte DTOs wünschst, können wir die PaymentHistory-DTOs direkt zurückgeben.
    }

    public function get_transaction_invoice_url(): string
    {
        return (string)$this->transaction['invoice_url'];
    }
    public function get_transaction_management_url(): string
    {
        return (string)$this->transaction['management_url'];
    }
    public function get_transaction_currency(): string
    {
        return $this->transaction['currency'];
    }
    public function get_transaction_current_number_of_payment(): int
    {
        return (int)$this->transaction['current_number_of_payment'];
    }
    public function get_transaction_overall_number_of_payments(): int
    {
        return (int)$this->transaction['overall_number_of_payments'];
    }
    public function get_days_between_first_and_second_payment(): int
    {
        return (int)$this->transaction['days_between_first_and_second_payment'];
    }
    public function get_days_between_second_and_nth_payment(): int
    {
        return (int)$this->transaction['days_between_second_and_nth_payment'];
    }

    // -------- VOUCHER
    public function get_voucher_code(): string
    {
        return $this->voucher['code'];
    }
    public function get_voucher_amount_net(): float
    {
        return (float)$this->voucher['amount_net'];
    }
    public function get_voucher_amount_gross(): float
    {
        return (float)$this->voucher['amount_gross'];
    }
    public function get_voucher_amount_vat(): float
    {
        return (float)$this->voucher['amount_vat'];
    }

    // -------- SOURCE
    public function get_source(): string
    {
        return $this->source;
    }

    // -------- PSEUDO-REPR
    public function __toString(): string
    {
        return json_encode($this->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '';
    }

    /** Optional: gib das validierte Model zurück */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
