-- Single-row config table for HeyDaniel+ pricing that's meant to be
-- editable without a code change - getMembershipMonthlyFee() (Components.php)
-- reads MonthlyFee from here for subscribeMembership()'s and
-- logMonthlyMembershipRevenue()'s revenue-log entries.
-- NOTE: this only controls what our own revenue log records - it does NOT
-- change what Stripe actually charges. The real charge amount comes from
-- STRIPE_MEMBERSHIP_PRICE_ID (.env), a Stripe Price configured on Stripe's
-- side. Changing MonthlyFee here without also updating the Stripe Price
-- (and vice versa) will make the revenue log disagree with what customers
-- are actually billed - keep both in sync by hand.
CREATE TABLE MembershipSettings (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  MonthlyFee DECIMAL(10,2) NOT NULL DEFAULT 9.99,
  DateUpdated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO MembershipSettings (MonthlyFee) VALUES (9.99);
