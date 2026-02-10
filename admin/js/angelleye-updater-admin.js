jQuery(document).ready(function ($) {
    function aeUpdateSyncStatus(message, type) {
        var $status = $('#ae-version-sync-status');
        if (!$status.length) {
            return;
        }

        $status.removeClass('notice-info notice-success notice-warning notice-error');
        $status.addClass('notice-' + (type || 'info')).show();
        $status.html('<p>' + message + '</p>');
    }

    function aeUpdateLatestVersionCell(productId, latestVersion) {
        if (!productId) {
            return;
        }

        var $wrap = $('.ae-latest-version-wrap[data-product-id="' + productId + '"]');
        if ($wrap.length) {
            $wrap.find('.ae-latest-version').text(latestVersion || '-');
        }
    }

    function aeToggleLatestVersionLoader(productId, show) {
        if (!productId) {
            return;
        }

        var $wrap = $('.ae-latest-version-wrap[data-product-id="' + productId + '"]');
        if (!$wrap.length) {
            return;
        }

        var $loader = $wrap.find('.ae-latest-version-loader');
        if (show) {
            $loader.addClass('is-spinning').show();
        } else {
            $loader.removeClass('is-spinning').hide();
        }
    }

    function aeRunLatestVersionSyncQueue() {
        if (typeof WTHelper === 'undefined' || !Array.isArray(WTHelper.sync_candidates)) {
            return;
        }

        if (!WTHelper.sync_candidates.length) {
            aeUpdateSyncStatus('All latest versions are fresh (last 24h).', 'success');
            return;
        }

        var queue = WTHelper.sync_candidates.slice(0);
        var total = queue.length;
        var completed = 0;
        var synced = 0;
        var skipped = 0;
        var failed = 0;

        aeUpdateSyncStatus('Checking latest versions... (0/' + total + ')', 'info');

        function next() {
            if (!queue.length) {
                var type = failed > 0 ? 'warning' : 'success';
                aeUpdateSyncStatus(
                    'Latest version sync finished. Synced: ' + synced + ', Skipped (<24h): ' + skipped + ', Failed: ' + failed + '.',
                    type
                );
                return;
            }

            var productId = queue.shift();
            aeToggleLatestVersionLoader(productId, true);

            $.post(WTHelper.ajax_url, {
                action: 'angelleye_sync_latest_version',
                security: WTHelper.sync_latest_nonce,
                product_id: productId
            }).done(function (response) {
                completed++;

                if (response && response.success && response.data) {
                    var status = response.data.status || '';
                    var latestVersion = response.data.latest_version || '-';
                    aeUpdateLatestVersionCell(productId, latestVersion);

                    if (status === 'synced') {
                        synced++;
                    } else if (status === 'skipped') {
                        skipped++;
                    } else {
                        failed++;
                    }
                } else {
                    failed++;
                }
            }).fail(function () {
                completed++;
                failed++;
            }).always(function () {
                aeToggleLatestVersionLoader(productId, false);
                aeUpdateSyncStatus('Checking latest versions... (' + completed + '/' + total + ')', 'info');
                next();
            });
        }

        next();
    }

    if ($('body').hasClass('dashboard_page_angelleye-helper') || $('body').hasClass('index_page_angelleye-helper-network')) {
        aeRunLatestVersionSyncQueue();
    }

    // Check if form is submitted and override
    $('.dashboard_page_angelleye-helper #activate-products').submit(function (event) {
        var license_keys_object = new Array();
        $('input[name^="license_keys["]').each(function () {
            if ($(this).val().length > 0) {
                var license_object = {name: $(this).attr("name").replace('license_keys[', '').replace(']', ''), key: $(this).val()};
                license_keys_object.push(license_object);
            }
        });
        if (license_keys_object.length > 0) {
            $('div.error.fade').remove();
            $('#activate-products table.licenses').css({opacity: 0.2});
            $('#activate-products').attr('disabled', 'disabled');
            var submit_data = {
                action: 'angelleye_activate_license_keys',
                license_data: license_keys_object,
                security: WTHelper.activate_license_nonce
            };
            $.post(WTHelper.ajax_url, submit_data, function (data) {
                var json_data = $.parseJSON(data);
                // Check if activation was successfull and reload page to show new activation
                if ('true' == json_data.success) {
                    window.location.href = json_data.url;
                }

                // If not sucessfull, show error messages.
                $('.angelleye-updater-wrap .nav-tab-wrapper').after(json_data.message);
                $('#activate-products table.licenses').css({opacity: 1});
                $('#activate-products').removeAttr('disabled');
                $('html, body').animate({
                    scrollTop: $('.angelleye-updater-wrap .nav-tab-wrapper').offset().top
                }, 2000);
            });
        }
        event.preventDefault();
    });
});