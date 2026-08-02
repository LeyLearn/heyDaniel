-- OrderRevenue: the tax-inclusive item subtotal + DeliveryFee. For same-day
-- this is what's authorized (held, capture_method=manual) on the card at
-- checkout; for standard orders it's charged in full immediately instead.
-- Tip is deliberately excluded and never raises the same-day hold - Stripe
-- can only capture up to what was authorized, never more.
-- DeliveryFee: same-day's $SAME_DAY_PAID_FEE (or $0.00 if free via
-- membership), or standard shipping's $STANDARD_DELIVERY_FEE/
-- $EXPRESS_DELIVERY_FEE depending on the delivery method the customer
-- picked at checkout - one column covers all four cases.
-- FinalOrderRevenue: OrderRevenue minus whatever didn't get fulfilled
-- (missing Process items, tax-inclusive), computed in finalizeOrder(). Can
-- only be <= OrderRevenue until a substitution/exchange mechanism exists to
-- add value back - Components.php's finalizeOrder() still branches on
-- FinalOrderRevenue > OrderRevenue (capture the full hold + a separate
-- difference charge) so it's ready for that once it lands.
-- TipAmount: always a raw dollar amount (never a percentage). Tip is held
-- separately from the main order (TipPaymentIntentId, manual capture) for
-- 3 days after finalizeOrder() to give the customer a window to change it -
-- see adjustTip() in Components.php. TipDecisionDeadline is when the
-- scheduled sweep (Server/Cron/CaptureHeldTips.php) auto-captures it if
-- untouched.
-- TaxAmount: the tax portion of OrderRevenue (subtotal * taxRate), set at
-- checkout - this is what OrderLiability used to hold before it was
-- repurposed below.
-- OrderLiability: NOT tax - what this order actually cost the company
-- (the shopper's real receipt total), entered by whichever employee
-- (HandlerId) fulfilled it, for profit/loss accounting. NULL until entered;
-- there's no entry UI for this yet (dashboard work, same as HandlerId).
-- Saved: sum of real dollar savings on this order - sale discounts
-- (Price - SalePrice), BOGO pairing discounts, the $SAME_DAY_PAID_FEE
-- waiver when same-day was free via membership, and $STANDARD_DELIVERY_FEE
-- when a member picked free-shipping instead of paying for standard.
-- Computed and written once at checkout in submitCheckout(), not touched
-- afterward. This replaced the
-- old CreditsEarned cashback formula - that concept moves to Users.Credits
-- as its own separate piece of work, not tracked per-order anymore.
-- OrderStatus: two different vocabularies depending on isSameDay, not one
-- shared set. Same-day: pending -> processing -> shipped -> delivered (or
-- cancelled). Standard: received -> packaging -> shipped -> location ->
-- delivered. 'shipped' is the only word shared between them - any query
-- that filters on OrderStatus without also filtering on isSameDay risks
-- matching the wrong order type once a standard order reaches that stage
-- (see getActiveSameDayOrderId()/getDisplayOrderStatus() in Components.php).
-- Standard orders are charged in full at checkout (no held payment, see
-- submitCheckout()), so there's no finalizeOrder()-equivalent capture step
-- for them - only same-day orders ever call finalizeOrder(). Nothing yet
-- progresses a standard order's status past 'received' (dashboard work, not
-- built - same bucket as HandlerId/OrderLiability).
-- Every order's line items live in Process (see Tables/OrderProcess.sql)
-- regardless of isSameDay - OrderTracking used to hold standard orders'
-- items separately but was dropped in favor of one shared items table.
-- IsMembershipCharge: marks a row as a HeyDaniel+ subscription charge
-- (fee from MembershipSettings) rather than a real product order -
-- ItemQuantity is always 0 and there are no Process rows for these. Written
-- directly by subscribeMembership() (first charge) and the monthly sweep
-- (Server/Cron/LogMembershipRevenue.php, logs the next month for every
-- still-active member) - deliberately not Stripe-webhook-driven, this is
-- our own DB-only revenue trail independent of Stripe delivering anything.
-- These rows use OrderStatus 'billed', a third status word deliberately
-- outside both order vocabularies above - a membership charge is never
-- fulfillable, so it must not match any status filter meant for real
-- orders.
CREATE TABLE OrderSent (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId INT(11) NOT NULL,
  ItemQuantity INT(11) NOT NULL,
  OrderRevenue DECIMAL(10,2) NOT NULL,
  FinalOrderRevenue DECIMAL(10,2) NOT NULL,
  TaxAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  OrderLiability DECIMAL(10,2) NULL,
  HandlerId INT(11) NOT NULL DEFAULT 0,
  TipAmount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  DeliveryFee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  Saved DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  isSameDay TINYINT(1) NOT NULL DEFAULT 0,
  IsMembershipCharge TINYINT(1) NOT NULL DEFAULT 0,
  OrderStatus VARCHAR(50) NOT NULL DEFAULT 'pending',
  DateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  TimeDelivered DATETIME NULL,
  ScheduledDeliveryWindow VARCHAR(100) NULL,
  ScheduledDeliveryStart DATETIME NULL,
  ScheduledDeliveryEnd DATETIME NULL,
  WasRescheduled TINYINT(1) NOT NULL DEFAULT 0,
  isClosed TINYINT(1) NOT NULL DEFAULT 0,
  TipPaymentIntentId VARCHAR(255) NULL,
  TipDecisionDeadline DATETIME NULL,
  FOREIGN KEY (UserId) REFERENCES Users(Id),
  INDEX idx_user_id (UserId),
  INDEX idx_order_status (OrderStatus),
  INDEX idx_date_added (DateAdded)
);