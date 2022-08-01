{if $widgetStatus eq -1}
    <div class="widget-content-padded widget-billing">
        <div class="color-pink">
            Please install or upgrade to the latest {{ $widgetTitleWithCompany }} Registrar Module.
            <span data-toggle="tooltip"
                title="The {{ $widgetTitleWithCompany }} Registrar Module is regularly maintained, download and documentation available at github."
                class="glyphicon glyphicon-question-sign"></span><br />
            <a href="{$repoLink}}" style="margin-top:15px;">
                <img src="{$logo}" height="40" />
            </a>
        </div>
    </div>
{else}

    {if $widgetStatus eq 0}
        <div class="widget-billing">
            <div class="row account-widget">
                <div class="col-sm-12">
                    <div class="item">
                        <div class="note">
                            {$widgetDisableMessage}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/if}
    <script type="text/javascript">
        const widgetAcc = new Widget(`{$widgetId}`, `{$widgetTTL}`, `{$widgetExpires}`, `{$widgetStatus}`, `{$widgetStatusIcon}`, `#cnrbalexpires{$widgetId}`);
        widgetAcc.mainWidget();
    </script>
{/if}

{if $widgetStatus eq 1}
    <div class="widget-billing">
        <div class="row account-widget">
            <div class="col-sm-6 bordered-right">
                {$balanceHTML}
            </div>
            <div class="col-sm-6">
                <div class="text-center" style="margin-top:15px;">
                    <img src="{$logo}" height="40">
                </div>
            </div>
        </div>
    </div>
    {if $refreshRequest eq ""}
        <script type="text/javascript">
            widgetAcc.cnrStartCounter();
        </script>
    {/if}
{/if}