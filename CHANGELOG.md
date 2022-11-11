2.6.3, 2022-11-10:
- [embedded] Do not create payment token if quote data has not changed.
- [embedded] Workarround to avoid the extra retry when the number of attempts is reached.
- Minor code fixes.

2.6.2, 2022-10-17:
- Bug fix: Fix error related to number of times a coupon is used when payment is failed.
- Bug fix: Fix error related to CURLOPT_SSL_VERIFYHOST supported values in REST API.

2.6.1, 2022-09-14:
- [embedded] Bug fix: Fix embedded fields displaying.
- Minor code fixes.

2.6.0, 2022-09-08:
- [embedded] Possibility to enable payment by alias with embedded payment fields.
- Update list of supported payment means.
- Added Portuguese translation.

2.5.13, 2022-08-16:
- Allow module to be installed when using php 8.1 or higher.

2.5.12, 2022-06-02:
- Bug fix: Fix session recovery issues on return page in Magento 2.4.x.
- Bug fix: Fix error when receiving IPN on cancellation calls.
- Add CSP configuration.
- Adapt code to PHP 8.x.
- Update list of supported payment means.

2.5.11, 2022-01-13:
- [embedded] Bug fix: Refresh minicart items count when payment is successful in Magento 2.4.x.
- [embedded] Bug fix: Validate Magento quote data before payment submit with REST API.
- [embedded] Update order summary and amount to pay with embedded payment fields when modifying minicart (workarround for a Magento bug).
- Fix session recovery issues with POST mode related to Samesite cookie in Magento 2.4.x.

2.5.10, 2021-10-05:
- [embedded] Bug fix: Do not refresh payment page automatically after an unrecoverable error.
- [embedded] Bug fix: Update payment token only if embedded payment is enabled.
- [embedded] Bug fix: Do not try to create form token if amount or currency are invalid.
- [embedded] Check standard submodule availability before creating form token.
- Dispatch restore_quote event when payment is not successful.
- Do not expand plugin configuration by default in Magento Back Office.

2.5.9, 2021-07-15:
- [embedded] Bug fix: Fix displayed installment amount in multi payment option.
- Display installments number in order details when it is available.

2.5.8, 2021-07-08:
- Display authorized amount in order details when it is available.

2.5.7, 2021-06-24:
- Send the relevant part of the current PHP version in vads_contrib field.
- Improve support e-mail display.

2.5.6, 2021-05-27:
- Possibility to open support issue from the plugin configuration panel or an order details page.
- Update 3DS management option description.
- Improve REST API keys configuration display.
- Improve plugin logs.

2.5.5, 2021-03-09:
- Use online payment means logos.

2.5.4, 2021-02-02:
- Fix installment details errors introduced in 2.5.3 version.

2.5.3, 2021-02-01:
- Fix installment information when saving the payment details in the Magento Back Office.
- Workarround to avoid conflict with "Payment & Shipping restrictions" plugin.

2.5.2, 2021-01-05:
- [embedded] Bug fix: Use the last version of PrototypeJS library when embedded payment fields option is enabled.
- Minor code fixes.

2.5.1, 2020-12-15:
- Display warning message on payment in iframe mode enabling.
- Bug fix: Manage PSP_100 errors when calling REST web services.
- Bug fix: Error 500 due to obsolete function (get_magic_quotes_gpc) in PHP 7.4.

2.5.0, 2020-11-25:
- [embedded] Bug fix: Empty cart to avoid double payments with REST API.
- [embedded] Possibility to display embedded payment fields in a popin.
- [alias] Added link to delete stored means of payment.
- [alias] Display the brand of the stored means of payment if payment by alias is enabled.
- [alias] Check alias validity before proceeding to payment.
- Possibility to configure REST API URLs.
- Refund payments using REST API v4.
- Accept and deny payments using REST API v4.
- Validate payments using REST API v4.
- [other] Possibility to propose other payment means by redirection.
- Improve configuration fields validation messages.
- Fix some translations.

2.4.11, 2020-11-02:
- [embedded] Bug fix: Display 3DS result for REST API payments.
- Bug fix: Do not re-create invoice if it already exists.
- Some minor fixes relative to configuration screen.

2.4.10, 2020-10-06:
- Update payment means list.

2.4.9, 2020-08-12:
- Bug fix: Error while trying to use WS services (accept, deny and validate payment, online refund).
- Update payment means list.

2.4.8, 2020-07-20:
- [embedded] Bug fix: Error due to strongAuthentication field renaming in REST token creation.
- [embedded] Bug fix: Do not cancel orders in status "Fraud suspected" when new failed IPN calls are made.
- Update payment means logos.
- Improve logged information.

2.4.7, 2020-06-19:
- [embedded] Bug fix: Amount did not include shipping fees when using embedded payment fields in some cases.
- [embedded] Bug fix: Compatibility of payment with embedded fields with Internet Explorer 11.
- [embedded] Bug fix: Error 500 due to riskControl modified format in REST response.
- Bug fix: Fix brand choice field management when returning to store for a payment with gift card.

2.4.6, 2020-05-12:
- Some minor fixes.
- [embedded] Bug fix: Use the correct return and private keys according to the plugin context mode.

2.4.5, 2020-04-23:
- Some minor fixes.
- [embedded] Bug fix: Load embedded payment fields JavaScript library inside require() function.

2.4.4, 2020-02-14:
- Bug fix: NoSuchEntityException occurs when trying to retrieve a removed product category.
- [embedded] Bug fix: Amount did not include shipping fees when using embedded payment fields if payment step is not refreshed.
- Bug fix: Payment information in order confirmation email was not correctly translated in some multistore cases.

2.4.3, 2020-01-20:
- Bug fix: Manage formKey for compatibility with Magento 2.3.x versions
- Bug fix: 3DS result is not correctly saved in Magento when using embedded payment fields.