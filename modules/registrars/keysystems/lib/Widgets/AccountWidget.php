<?php

namespace WHMCS\Module\Registrar\Keysystems\Widgets;

use WHMCS\Module\Registrar\Keysystems\Widgets\Balance;
use WHMCS\Config\Setting;

/**
 * CentralNic Reseller Account Widget.
 */
class AccountWidget extends \WHMCS\Module\AbstractWidget
{
    /** @var string */
    protected $title = 'CentralNic Reseller Account Overview';
    /** @var int */
    protected $cacheExpiry = 120;
    /** @var int */
    protected $weight = 150;

    /** @var string */
    public static $widgetid = "CNRAccountWidget";
    /** @var int */
    public static $sessionttl = 3600; // 1h

    /** @var \WHMCS\View\Asset */
    protected $assetHelper;

    /**
     * constructor
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $wrpath = \DI::make("asset")->getWebRoot();
        $this->assetHelper = new \WHMCS\View\Asset($wrpath);
        add_hook("AdminAreaHeadOutput", 1, function ($vars) {
            $cssPath = $this->getAssetPath('css');
            $jsPath = $this->getAssetPath('js');
            if ($vars["pagetitle"] === "Dashboard") {
                return <<<HTML
                <style type="text/css" src="{$cssPath}/widget.css"></style>
                <script type="text/javascript" src="{$jsPath}/widget.js"></script>
                HTML;
            }
        });
    }

    /**
     * Overrides default widget id
     *
     * @return string
     */
    public function getId()
    {
        return self::$widgetid;
    }

    /**
     * Fetch data that will be provided to generateOutput method
     * @return array<string, mixed> data array
     */
    public function getData()
    {
        $id = $this->getId();
        $statusId = $id . "status";
        // status toggle
        $status = \App::getFromRequest("status");
        if ($status !== "") {
            if (in_array($status, [0, 1])) {
                Setting::setValue($statusId, $status);
            }
        } else {
            $status = Setting::getValue($statusId);
        }
        $status = (int)$status;

        // hidden widgets -> don't load data
        $isHidden = in_array($id, $this->adminUser->hiddenWidgets);
        if ($isHidden) {
            return [
                "status" => 0,
                "widgetid" => $id
            ];
        }

        // load data
        $data = [
            "status" => is_null($status) ? 1 : (int)$status,
            "widgetid" => $id
        ];

        if (
            !empty($_REQUEST["refresh"]) // refresh request
            || !isset($_SESSION[$id]) // Session not yet initialized
            || (time() > $_SESSION[$id]["expires"]) // data cache expired
        ) {
            $_SESSION[$id] = [
                "expires" => time() + self::$sessionttl,
                "ttl" =>  self::$sessionttl
            ];
        }

        $balance = ($data['status'] == 1) ? new Balance() : null;

        return array_merge($data, [
            "balance" => $balance,
        ]);
    }

    /**
     * generate widget"s html output
     * @param mixed $data input data (from getData method)
     * @return string html code
     */
    public function generateOutput($data)
    {
        $widgetStatusIcon = ($data['status'] === 1) ? "on" : "off";
        $widgetStatus = $data["status"];
        $balance = $data["balance"];
        $widgetExpires = $_SESSION[$data["widgetid"]]["expires"] - time();
        $widgetTTL = $_SESSION[$data["widgetid"]]["ttl"];
        $templatevars = [
            "WEB_ROOT" => $this->assetHelper->getWebRoot(),
            "BASE_PATH_CSS" => $this->getAssetPath("css"),
            "BASE_PATH_JS" => $this->getAssetPath("js"),
            "BASE_PATH_IMG" => $this->getAssetPath("images"),
            "widgetExpires" => $widgetExpires,
            "widgetTTL" => $widgetTTL,
            "widgetId" => $this->getId(),
            "widgetStatus" => $widgetStatus,
            "widgetStatusIcon" => $widgetStatusIcon,
            "balanceHTML" => !empty($balance) ? $balance->toHTML() : null,
            "refreshRequest" => !empty($_REQUEST["refresh"]),
            "widgetTitleWithCompany" => "CentralNic Reseller",
            "widgetDisableMessage" => "Widget is currently disabled. Use the first icon for enabling.",
            "repoLink" => "https://github.com/rrpproxy/whmcs-rrpproxy-registrar",
            "logo" => $this->assetHelper->getWebRoot() . "/modules/registrars/keysystems/logo.png",
        ];

        $smarty = new \WHMCS\Smarty();
        foreach ($templatevars as $key => $value) {
            $smarty->assign($key, $value);
        }

        $defaulttplfolder = array_pop($smarty->getTemplateDir());
        $newtplfolder = explode(DIRECTORY_SEPARATOR, $defaulttplfolder);
        array_splice($newtplfolder, count($newtplfolder) - 2, 0, $this->getAssetPath());
        $newtplfolder = implode(DIRECTORY_SEPARATOR, $newtplfolder);
        $smarty->setTemplateDir($newtplfolder);
        return $smarty->fetch("index.tpl");
    }

    /**
     * Get Assets Path
     * @param string $asset
     * @return string
     */
    protected function getAssetPath($asset = null): string
    {
        $assets = [$this->assetHelper->getWebRoot(), "modules", "registrars", "keysystems", "lib", "assets"];

        if (!empty($asset)) {
            $assets[] = $asset;
        }

        return  implode(DIRECTORY_SEPARATOR, $assets);
    }
}
