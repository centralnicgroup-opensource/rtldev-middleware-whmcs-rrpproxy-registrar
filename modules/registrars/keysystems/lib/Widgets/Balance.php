<?php

namespace WHMCS\Module\Registrar\Keysystems\Widgets;

use WHMCS\Module\Registrar\Keysystems\Widgets\AccountWidget;
use WHMCS\Module\Registrar\Keysystems\Widgets\Currency;

class Balance
{
    /** @var array<string, mixed> */
    private $data = [];
    /** @var Currency */
    private $currencyObject = null;

    public function __construct()
    {
        /**
         * @codeCoverageIgnore
         */
        // init status
        if (isset($_SESSION[AccountWidget::$widgetid]["data"])) { // data cache exists
            $this->data = $_SESSION[AccountWidget::$widgetid]["data"];
        } else {
            $this->data = [];
            $accountsStatus = keysystems_getAccountDetails(); // @codeCoverageIgnore
            if ($accountsStatus["success"]) {
                $this->data['amount'] = $accountsStatus['amount'];
                $this->data['currency'] = $accountsStatus['currency'];
                if (isset($accountsStatus['deposit'])) {
                    $this->data['deposit'] = $accountsStatus['deposit'];
                }
                $_SESSION[AccountWidget::$widgetid]["data"] = $this->data;
            }
        }

        // a reference to the currency instance in main class: AccountWidget
        $this->currencyObject = new Currency();
    }

    /**
     * get balance data
     * @return array<string, mixed>|null
     */
    public function getData(): ?array
    {
        if (empty($this->data)) {
            return null;
        }
        $amount = floatval($this->data["amount"]);
        $deposit = floatval($this->data["deposit"] ?? 0);
        $fundsav = $amount - $deposit;
        $currency = $this->data["currency"];
        $currencyid = $this->currencyObject->getId($currency);
        return [
            "amount" => $amount,
            "deposit" => $deposit,
            "fundsav" => $fundsav,
            "currency" => $currency,
            "currencyID" => $currencyid,
            "hasDeposits" => $deposit > 0,
            "isOverdraft" => $fundsav < 0
        ];
    }

    /**
     * get formatted balance data
     * @return array<string, mixed>|null
     */
    public function getDataFormatted(): ?array
    {
        $data = $this->getData();
        if (is_null($data)) {
            return null;
        }
        $keys = ["amount", "deposit", "fundsav"];
        if (is_null($data["currencyID"])) {
            foreach ($keys as $key) {
                $data[$key] = number_format($data[$key], 2, ".", ",") . " " . $data["currency"];
            }
        } else {
            foreach ($keys as $key) {
                $data[$key] = formatCurrency($data[$key], $data["currencyID"]);
            }
        }
        return $data;
    }

    /**
     * generate balance as HTML
     * @return string
     */
    public function toHTML(): string
    {
        $data = $this->getDataFormatted();
        if (is_null($data)) {
            return <<<HTML
                <div class="widget-content-padded widget-billing">
                    <div class="color-pink">Loading Account Data failed.</div>
                </div>
            HTML;
        }

        $balanceColor = $data["isOverdraft"] ? "pink" : "green";
        //$expires = ($_SESSION[AccountWidget::$widgetid]["expires"] ?? 0) - time();
        //$ttl = $_SESSION[AccountWidget::$widgetid]["ttl"];

        $baseHTML = <<<HTML
            <div class="item text-right">
                <div class="data color-{$balanceColor}">{$data["amount"]}</div>
                <div class="note">Account Balance</div>
            </div>
        HTML;
        if (!$data["hasDeposits"]) {
            return $baseHTML;
        }

        return <<<HTML
            {$baseHTML}
            <div class="item text-right">
                <div class="data color-pink">{$data["deposit"]}</div>
                <div class="note" data-toggle="tooltip" title="Deposits are automatically withdrawn from your account balance to cover impending backorders and will be returned if a backorder registration is unsuccessful.">Reserved Deposits <span class="glyphicon glyphicon-question-sign"></span></div>
            </div>
            <div class="item bordered-top text-right">
                <div class="data color-{$balanceColor}">{$data["fundsav"]}</div>
                <div class="note">Available Funds</div>
            </div>
        HTML;
    }
}
