class Widget {
  constructor(widgetId, ttl, expires, status, ico, timerSelector) {
    this.widgetId = widgetId;
    this.ttl = ttl;
    this.expires = expires;
    this.status = status;
    this.ico = ico;
    this.timerSelector = timerSelector;
  }

  cnrRefreshWidget(widgetName, requestString, cb) {
    const panelBody = $('.panel[data-widget="' + widgetName + '"] .panel-body');
    const url = WHMCS.adminUtils.getAdminRouteUrl(
      "/widget/refresh&widget=" + widgetName + "&" + requestString
    );
    panelBody.addClass("panel-loading");
    return WHMCS.http.jqClient
      .post(
        url,
        function (data) {
          this.lastExecution = true;
          panelBody.html(data.widgetOutput);
          panelBody.removeClass("panel-loading");
        },
        "json"
      )
      .always(cb);
  }

  mainWidget() {
    let widgetId = this.widgetId;
    let ttl = this.ttl;
    let expires = this.expires;
    let widgetStatus = this.status;
    let ico = this.ico;
    if (
      $(`#panel` + widgetId + ` .widget-tools .cnr-widget-toggle`).length === 0
    ) {
      $(`#panel` + widgetId + ` .widget-tools`).prepend(
        ` <a href = "#" class="cnr-widget-toggle" data-status="` +
          widgetStatus +
          `">
                <i class=\"fas fa-toggle-` +
          ico +
          `\"></i>
                          </a> `
      );
    } else {
      $(`#panel` + widgetId + ` .widget-tools .cnr-widget-toggle`).attr(
        "data-status",
        widgetStatus
      );
    }

    if (!$(this.timerSelector).length) {
      $(`#panel` + widgetId + ` .widget-tools`).prepend(
        ` <a href="#" class="cnr-widget-expires" data-expires="` +
          expires +
          `"
                data-ttl="` +
          ttl +
          `">
                <span id="cnrbalexpires` +
          widgetId +
          `" 
                class="ttlcounter"> </span>
                          </a>`
      );
    }

    $(this.timerSelector)
      .data("ttl", ttl)
      .data("expires", expires)
      .html(this.cnrSecondsToHms(expires, ttl));
    if ($(`#panel` + widgetId + ` .cnr-widget-toggle`).data("status") === 1) {
      $(`#panel` + widgetId + ` .cnr-widget-expires`).show();
    } else {
      $(`#panel` + widgetId + ` .cnr-widget-expires`).hide();
    }

    $(`#panel` + widgetId + ` .cnr-widget-toggle`)
      .off()
      .on(
        "click",
        { toggleSelector: `#panel` + widgetId + ` .cnr-widget-toggle` },
        function (event) {
          event.preventDefault();
          const icon = $(event.data.toggleSelector).find(
            'i[class^="fas fa-toggle-"]'
          );
          const widget = $(event.data.toggleSelector)
            .closest(".panel")
            .data("widget");
          const newstatus =
            1 - $(event.data.toggleSelector).attr("data-status");
          icon.attr("class", "fas fa-spinner fa-spin");
          this.cnrRefreshWidget(
            widget,
            "refresh=1&status=" + newstatus,
            function () {
              icon.attr(
                "class",
                "fas fa-toggle-" + (newstatus === 0 ? "off" : "on")
              );
              $(event.data.toggleSelector).attr("data-status", newstatus);
              if (newstatus === 1) {
                $(`#panel` + widgetId + ` .cnr-widget-expires`).show();
              } else {
                $(`#panel` + widgetId + ` .cnr-widget-expires`).hide();
              }
              packery.fit(event.data.toggleSelector);
              packery.shiftLayout();
            }
          );
        }.bind(this)
      );
  }

  cnrStartCounter() {
    let sel = this.timerSelector;
    if (!$(sel).length) {
      return;
    }
    setInterval(this.cnrDecrementCounter.bind(this), 1000);
  }

  cnrDecrementCounter() {
    let sel = this.timerSelector;
    if (!$(sel).length) {
      return;
    }
    let expires = $(sel).data("expires") - 1;
    const ttl = $(sel).data("ttl");
    $(sel).data("expires", expires);
    $(sel).html(this.cnrSecondsToHms(expires, ttl));
  }

  cnrSecondsToHms(d, ttl) {
    d = Number(d);
    const ttls = [3600, 60, 1];
    let units = ["h", "m", "s"];
    let vals = [
      Math.floor(d / 3600), // h
      Math.floor((d % 3600) / 60), // m
      Math.floor((d % 3600) % 60), // s
    ];
    let steps = ttls.length;
    ttls.forEach((row) => {
      if (ttl / row === 1 && ttl % row === 0) {
        steps--;
      }
    });
    vals = vals.splice(vals.length - steps);
    units = units.splice(units.length - steps);
    let html = "";
    vals.forEach((val, idx) => {
      html += " " + val + units[idx];
    });
    return html.substr(1);
  }
}
