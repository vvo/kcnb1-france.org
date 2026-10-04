<div class="filter-container">
    <input id="auditlog-search" name="search" type="search" value="%%SUCURI.AuditLog.Search%%"
           placeholder="{{Search audit trails}}" aria-label="{{Search audit trails}}"
           data-cy="sucuriscan_auditlogs_search">

    %%%SUCURI.AuditLog.Filters%%%

    <button id="filter-button" type="submit" class="button button-primary" data-cy="sucuriscan_auditlogs_filter_button">
        {{Filter}}
    </button>

    <button id="clear-filter-button" type="submit" class="button button-secondary"
            data-cy="sucuriscan_auditlogs_clear_filter_button">
        {{Clear Filters}}
    </button>
</div>
