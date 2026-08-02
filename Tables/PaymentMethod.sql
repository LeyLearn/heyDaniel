-- ShowSavedCard: when 0, listPaymentMethods() reports zero cards to every
-- frontend surface that asks - the checkout/membership-modal "use your
-- saved card" shortcuts AND the account Payment Methods page. Not partial
-- hiding, not "still visible in account settings" - if the customer said
-- don't save it, nothing in the app shows it again. The card stays attached
-- in Stripe regardless (that's what makes recurring billing and
-- finalizeOrder()/adjustTip()'s off-session reuse work, a Stripe
-- requirement, not a user preference) and this row is always written/kept
-- current regardless of this flag - it's purely about what the frontend is
-- allowed to show. Driven by the "Save card for faster checkout" checkbox
-- on both submitCheckout() and subscribeMembership() - must reflect
-- whatever that checkbox actually said each time, never hardcoded to 1.
-- Silently re-enabling this after a customer explicitly said not to save
-- their card is exactly the kind of thing that gets a business sued.
CREATE TABLE CustomerPaymentMethod (
  Id INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
  UserId INT(11) NOT NULL,
  PaymentMethod VARCHAR(50) NOT NULL,
  StripeCustomerId VARCHAR(255) NOT NULL,
  StripePaymentIntentId VARCHAR(255) NULL,
  StripeSubscriptionId VARCHAR(255) NULL,
  ShowSavedCard TINYINT(1) NOT NULL DEFAULT 1,
  DateAdded DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user (UserId),
  FOREIGN KEY (UserId) REFERENCES Users(Id) ON DELETE CASCADE,
  INDEX idx_stripe_customer_id (StripeCustomerId)
);