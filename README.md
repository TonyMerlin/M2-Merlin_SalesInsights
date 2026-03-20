# Merlin_SalesInsights

## What it does
- Aggregates sales by manufacturer + attribute set
- Stores daily, weekly, and monthly rollups
- Exposes an admin grid under Reports > Sales Insights
- Supports filtering by period type, date range, manufacturer, attribute set, store, revenue, orders, qty, etc.

## Install
bin/magento module:enable Merlin_SalesInsights
bin/magento setup:upgrade
bin/magento cache:flush

## Initial rebuild
bin/magento merlin:salesinsights:rebuild 2025-01-01 2026-03-20

## Cron
Runs daily at 02:25 and rebuilds the last 400 days.

## Default configuration
- Manufacturer attribute code: manufacturer
- Included statuses:
  - processing
  - complete
  - delivery_confirmed
  - finance_accepted
  - delivery_confirmed_old
  - order_complete

## Notes
- Parent rows are counted; child rows are ignored.
- Uses base currency amounts.
- Excludes canceled orders by excluding state = canceled.
