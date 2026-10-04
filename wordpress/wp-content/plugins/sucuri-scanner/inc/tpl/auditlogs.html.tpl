<script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase:false */
    jQuery(document).ready(function ($) {
        function parseFiltersToQueryParams(filters) {
            const queryParams = new URLSearchParams();

            for (const key in filters) {
                if (filters.hasOwnProperty(key) && filters[key] !== null && filters[key] !== undefined) {
                    queryParams.append(key, filters[key]);
                }
            }

            return queryParams.toString();
        }

        var writeQueueSize = function (queueSize, issetApiUrl) {
            if (queueSize === 0 || !issetApiUrl) {
                $('.sucuriscan-auditlogs-sendlogs-response').html('');
                $('.sucuriscan-sendlogs-panel').addClass('sucuriscan-hidden');
            } else {
                var msg = '\x20logs in the queue\x20&mdash;\x20';
                $('.sucuriscan-auditlogs-sendlogs-response').html((queueSize).toString() + msg);
                $('.sucuriscan-sendlogs-panel').removeClass('sucuriscan-hidden');
            }
        };

        var sucuriscanLoadAuditLogs = function (page, filters = {}) {
            var url = '%%SUCURI.AjaxURL.Dashboard%%';

            if (page !== undefined && page > 0) {
                url += '&paged=' + page;
            }

            if (filters !== undefined && Object.keys(filters).length > 0) {
                url += '&' + parseFiltersToQueryParams(filters);
            }

            $('.sucuriscan-auditlog-response').html('<em>{{Loading...}}</em>');
            $('.sucuriscan-auditlog-status').html('{{Loading...}}');
            $('.sucuriscan-pagination-loading').html('{{Loading...}}');
            $('.sucuriscan-pagination-panel').addClass('sucuriscan-hidden');
            $('.sucuriscan-auditlog-footer').addClass('sucuriscan-hidden');

            $.post(url, {
                action: 'sucuriscan_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action: 'get_audit_logs',
            }, function (data) {
                $('.sucuriscan-pagination-loading').html('');

                writeQueueSize(data.queueSize, data.status != 'API is not available; using local queue');

                var status = data.status;

                if (data.limited) {
                    status += (status ? ' &mdash; ' : '') + '{{Results are limited to the latest audit trails available.}}';
                }

                $('.sucuriscan-auditlog-status').html(status);
                $('.sucuriscan-auditlog-footer').removeClass('sucuriscan-hidden');

                if (data.filters !== undefined) {
                    $('#sucuriscan-filters').html(data.filters);

                    $('#startDate').hide();
                    $('#endDate').hide();

                    if (filters.time === 'custom') {
                        $('#startDate').show();
                        $('#endDate').show();
                    }
                }

                if (data.filtersStatus === 'active') {
                    $('#clear-filter-button').show();
                }

                if (data.content !== undefined) {
                    $('.sucuriscan-auditlog-response').html(data.content);

                    if (data.pagination !== '') {
                        $('.sucuriscan-pagination-panel').removeClass('sucuriscan-hidden');
                        $('.sucuriscan-auditlog-table .sucuriscan-pagination').html(data.pagination);
                    }
                } else if (typeof data === 'object') {
                    $('.sucuriscan-auditlog-response').html(
                        '<textarea class="sucuriscan-full-textarea">' +
                        JSON.stringify(data) + '</textarea>');
                    $('.sucuriscan-auditlog-table .sucuriscan-pagination').html('');
                } else {
                    $('.sucuriscan-auditlog-response').html(data);
                    $('.sucuriscan-auditlog-table .sucuriscan-pagination').html('');
                }
            });
        }

        setTimeout(function () {
            sucuriscanLoadAuditLogs(0);
        }, 100);

        $('.sucuriscan-auditlog-table').on('click', '.sucuriscan-pagination-link', function (event) {
            event.preventDefault();
            window.scrollTo(0, $('#sucuriscan-integrity-response').height() + 100);

            var url = new URL(event.target.href);
            var page = url.searchParams.get('paged');

            var filters = {};

            ['time', 'posts', 'logins', 'users', 'plugins', 'themes', 'files', 'events', 'search']
                .forEach(function (filter) {
                    if (url.searchParams.get(filter) !== null) {
                        filters[filter] = url.searchParams.get(filter);
                    }
                });

            if (url.searchParams.get('startDate') !== null) {
                filters.startDate = url.searchParams.get('startDate');
            }

            if (url.searchParams.get('endDate') !== null) {
                filters.endDate = url.searchParams.get('endDate');
            }

            sucuriscanLoadAuditLogs($(this).attr('data-page'), filters);
        });

        $('.sucuriscan-auditlog-table').on('click', '.sucuriscan-auditlogs-sendlogs', function (event) {
            event.preventDefault();

            $('.sucuriscan-sendlogs-panel').attr('content', '');
            $('.sucuriscan-auditlogs-sendlogs-response').html('{{Loading...}}');

            $.post('%%SUCURI.AjaxURL.Dashboard%%', {
                action: 'sucuriscan_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action: 'auditlogs_send_logs',
            }, function (data) {
                sucuriscanLoadAuditLogs(0);

                setTimeout(function () {
                    var tooltipContent =
                        '{{Total logs in the queue:}} {TTLLOGS}<br>' +
                        '{{Maximum execution time:}} {MAXTIME}<br>' +
                        '{{Successfully sent to the API:}} {SUCCESS}<br>' +
                        '{{Total request timeouts (failures):}} {FAILURE}<br>' +
                        '{{Total execution time:}} {ELAPSED} secs';
                    $('.sucuriscan-sendlogs-panel')
                        .attr('content', tooltipContent
                            .replace('{MAXTIME}', data.maxtime)
                            .replace('{TTLLOGS}', data.ttllogs)
                            .replace('{SUCCESS}', data.success)
                            .replace('{FAILURE}', data.failure)
                            .replace('{ELAPSED}', data.elapsed)
                        );
                }, 200);
            });
        });

        document.body.addEventListener('click', function (e) {
            if (e.target.id == 'filter-button') {
                var filters = {};

                var search = $('#auditlog-search').val().trim();
                var time = $('#time').val();
                var posts = $('#posts').val();
                var logins = $('#logins').val();
                var users = $('#users').val();
                var plugins = $('#plugins').val();
                var themes = $('#themes').val();
                var files = $('#files').val();
                var events = $('#events').val();

                if (search !== '') filters.search = search;
                if (time !== 'all time') filters.time = time;
                if (posts !== 'all posts') filters.posts = posts;
                if (logins !== 'all logins') filters.logins = logins;
                if (users !== 'all users') filters.users = users;
                if (plugins !== 'all plugins') filters.plugins = plugins;
                if (themes !== 'all themes') filters.themes = themes;
                if (files !== 'all files') filters.files = files;
                if (events !== 'all events') filters.events = events;

                if (time === 'custom') {
                    var startDate = $('#startDate').val();
                    var endDate = $('#endDate').val();

                    if (startDate !== '') filters.startDate = startDate;
                    if (endDate !== '') filters.endDate = endDate;
                }

                sucuriscanLoadAuditLogs(0, filters);
            }

            if (e.target.id == 'clear-filter-button') {
                sucuriscanLoadAuditLogs(0);
            }
        });

        document.body.addEventListener('keydown', function (e) {
            if (e.target.id === 'auditlog-search' && e.key === 'Enter') {
                e.preventDefault();
                $('#filter-button').trigger('click');
            }
        });

        document.body.addEventListener('change', function (e) {
            if (e.target.id === 'time') {
                if ($(e.target).val() === 'custom') {
                    $('#startDate').show();
                    $('#endDate').show();
                } else {
                    $('#startDate').hide();
                    $('#endDate').hide();
                }
            }
        });
    });
</script>

<div class="sucuriscan-panel">
    <div class="sucuriscan-auditlog-table">
        <div id="sucuriscan-filters"></div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <p>
                {{The CSV contains all audit trails currently stored on this website.}}
                <a id="download-auditlogs-link" href="%%SUCURI.AuditLogs.DownloadURL%%"
                   data-cy="sucuriscan_auditlogs_download_link">{{Download CSV}}</a>
            </p>
        </div>

        <div class="sucuriscan-auditlog-response" data-cy="sucuriscan_auditlog_response_loading">
            <em>{{Loading...}}</em>
        </div>

        <div class="sucuriscan-clearfix sucuriscan-pagination-panel">
            <ul class="sucuriscan-pull-left sucuriscan-pagination">
                <!-- Populated via JavaScript -->
            </ul>

            <div class="sucuriscan-pull-right sucuriscan-pagination-loading">
                <!-- Populated via JavaScript -->
            </div>
        </div>

        <div class="sucuriscan-clearfix sucuriscan-auditlog-footer" data-cy="sucuriscan_audit_logs_footer">
            <div class="sucuriscan-pull-left sucuriscan-hidden sucuriscan-tooltip
            sucuriscan-sendlogs-panel" tooltip-width="250" tooltip-html="true">
                <small class="sucuriscan-auditlogs-sendlogs-response"></small>
                <small><a href="#" class="sucuriscan-auditlogs-sendlogs"
                          data-cy="sucuriscan_dashboard_send_audit_logs_submit">{{Send Logs}}</a></small>
            </div>

            <div class="sucuriscan-pull-right">
                <small class="sucuriscan-auditlog-status"></small>
            </div>
        </div>
    </div>
</div>
