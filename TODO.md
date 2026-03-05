# TODO: Fix Stocks Not Appearing on Dashboard

## Issue
Added stocks were not appearing on the dashboard because the dashboard calculates totalStocks by summing Productss.quantity, but adding Stocks entities did not update Productss.quantity.

## Solution
Update StocksController to modify Productss.quantity when creating, editing, or deleting stocks.

## Tasks
- [x] Update new() method in StocksController to add quantityChange to Productss.quantity after persisting stock.
- [x] Update edit() method in StocksController to adjust Productss.quantity based on the difference in quantityChange.
- [x] Update delete() method in StocksController to subtract quantityChange from Productss.quantity before deleting stock.

## Followup Steps
- Test the application to ensure stocks now update the dashboard correctly.
- Verify that creating, editing, and deleting stocks properly reflect in totalStocks on the dashboard.
