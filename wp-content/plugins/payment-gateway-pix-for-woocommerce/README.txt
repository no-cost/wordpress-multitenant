=== Pix for WooCommerce ===
Contributors: linknacional
Tags: woocommerce, pagamento, pix, c6, brasil
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.5.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://paraquemdoar.org/doar/

Easily accept Pix payments in your WooCommerce store via Pix Key, PagHiper, or C6 Bank. The complete Pix solution for Brazil.


== Description ==

Integrate **Pix**, Brazil's revolutionary instant payment system, into your [WooCommerce](https://www.linknacional.com.br/wordpress/woocommerce/) store with our powerful and flexible gateway plugin.

Offer your customers a fast, secure, and modern checkout experience. This plugin allows you to receive Pix payments through five different methods, giving you the flexibility to choose what works best for your business:

* **Direct Pix Key**
* **PagHiper Gateway**
* **C6 Bank Integration**
* **Cielo Pix Gateway**
* **Rede Pix Gateway**

== Features ==

* **Five Integration Modes:** Choose the best way to receive Pix payments.
    * **Direct Pix Key:** The simplest setup. Just enter your registered Pix key (CPF/CNPJ, E-mail, Phone, Randon Code) and you're ready to sell. The plugin generates a "copy and paste" code and a QR Code for your customers.
    * **PagHiper Gateway:** Automate your sales with [PagHiper](https://www.paghiper.com). This integration provides a robust gateway to generate charges and automatically confirm payments, giving you and your customers more security and convenience.
    * **C6 Bank Integration:** Perfect for C6 Bank clients. Connect your account directly for seamless Pix charge generation and real-time automatic payment confirmation.
    * **Cielo Pix Gateway:** Accept Pix payments through Cielo, one of Brazil's largest payment processors, with automatic order confirmation.
    * **Rede Pix Gateway:** Accept Pix payments through Rede (Itaú), with automatic order confirmation and robust integration.

* **Automatic QR Code Generation:** A dynamic QR Code is generated for every order.
* **"Pix Copia e Cola" Button:** A one-click button for customers on desktop to easily copy the payment code.
* **Automatic Order Confirmation:** Integrations with PagHiper, C6 Bank, Cielo, and Rede handle payment confirmation automatically, updating the order status in WooCommerce.
* **Easy to Configure:** A clean and intuitive settings panel to get you started in minutes.

Start selling with Pix today and boost your conversions in the Brazilian market!

== Screenshots ==

1. C6 Pix payment template on checkout.
2. C6 Pix gateway payment option on the WooCommerce checkout page.
3. C6 Pix gateway configuration page in the admin panel.
4. Basic Pix payment template on checkout.
5. Basic Pix gateway payment option on the WooCommerce checkout page.
6. Basic Pix gateway configuration page in the admin panel.
7. PagHiper Pix payment template on checkout.
8. PagHiper Pix gateway payment option on the WooCommerce checkout page.
9. PagHiper Pix gateway configuration page in the admin panel.

== Recommended Integrations ==

Please note that this plugin provides a direct integration for Pix Key, PagHiper, and C6 Bank. If you need to process Pix payments through other major Brazilian gateways, we recommend the following official plugins:

* **Payment Method Discounts:** To configure discounts based on payment methods, we recommend using the [Link Invoice for WooCommerce](https://br.wordpress.org/plugins/invoice-payment-for-woocommerce/) plugin.
* **Cielo:** To accept Pix payments through the Cielo gateway, we recommend using the [Cielo para WooCommerce](https://br.wordpress.org/plugins/lkn-wc-gateway-cielo/) plugin.
* **eRede (Itaú):** For processing Pix payments with the eRede (Itaú) gateway, we recommend the [Rede para WooCommerce](https://wordpress.org/plugins/woo-rede/) plugin.


== Minimum Requirements ==

For this plugin to work correctly, you will need:

* WordPress version 5.0 or later.
* WooCommerce version 5.0 or later.
* PHP version 7.4 or later.
* An active SSL Certificate is highly recommended for secure transactions.


== Installation ==

There are two ways to install the Pix Payment Gateway for WooCommerce plugin:

= From your WordPress Dashboard (Recommended) =

1. In your WordPress admin panel, navigate to **Plugins > Add New**.
2. Use the search bar to find "Pix Payment Gateway for WooCommerce".
3. Locate the plugin in the search results and click the **Install Now** button.
4. Once the installation is complete, click the **Activate** button.

= Manual Upload via .zip File =

1. Download the plugin's `.zip` file from the official WordPress.org plugin page.
2. In your WordPress admin panel, navigate to **Plugins > Add New**.
3. At the top of the page, click the **Upload Plugin** button.
4. Click **Choose File** and select the `.zip` file you downloaded in step 1.
5. Click **Install Now**.
6. After the installation is complete, click the **Activate Plugin** button.

After activation, you can configure the plugin by navigating to **WooCommerce > Settings > Payments**.

== Usage ==

Configuring the plugin is straightforward. Follow these steps to set up your desired Pix payment method.

= Activating the Payment Method =

1. In your WordPress admin area, go to **WooCommerce > Settings**.
2. Click on the **Payments** tab.
3. You will see a list of all available payment gateways. Locate the one you want to configure:
    * **Pix Key**
    * **Pix with PagHiper**
    * **Pix C6**
4. Enable your chosen method by using the toggle switch.
5. Click the **Manage** button to enter its specific settings.

= Configuration Details =

After clicking **Manage**, you will need to fill in the required information for your chosen method:

* **For Pix Key:**
    1. Enter a **Title** and **Description** that will be shown to your customer at checkout.
    2. Add your registered **Pix Key** (e.g., your e-mail, phone number, or CNPJ).
    3. Click **Save changes**.

* **For Pix with PagHiper:**
    1. Enter the **Title** and **Description**.
    2. Input your PagHiper **API Key** and **Token** (found in your PagHiper dashboard).
    3. Configure any other available settings to your preference.
    4. Click **Save changes**.

* **For Pix C6:**
    1. Enter the **Title** and **Description**.
    2. Input your C6 Bank API credentials: **Client ID** and **Client Secret**.
    3. Adjust additional settings as needed.
    4. Click **Save changes**.

* **For Cielo Pix Gateway:**
    1. Enter the **Title** and **Description**.
    2. Input your Cielo **Merchant Id** and **Merchant Key** (found in your Cielo dashboard).
    3. Configure any other available settings to your preference.
    4. Click **Save changes**.

* **For Rede Pix Gateway:**
    1. Enter the **Title** and **Description**.
    2. Input your Rede **Pv** and **Token** (found in your Rede dashboard).
    3. Configure any other available settings to your preference.
    4. Click **Save changes**.

Your configured Pix method will now be available for customers at checkout.

== Enjoying the Plugin? ==

If you find the **Pix Payment Gateway for WooCommerce** plugin useful, please consider leaving a 5-star review on WordPress.org.

Your feedback is invaluable to us. It not only helps other store owners discover the plugin but also motivates us to continue developing and improving it. A positive review is the best way to show your support for our work.

[**Leave your review here!**](https://wordpress.org/support/plugin/payment-gateway-pix-for-woocommerce/reviews/#new-post)

Thank you for being a part of our community!


== Frequently Asked Questions ==

= Which integration method should I use: Pix Key, PagHiper, or C6 Bank? =

* This depends on your needs:
* **Pix Key:** Choose this for the simplest and quickest setup. It's ideal if you are just starting or have a low volume of sales and don't mind confirming payments manually by checking your bank statement.
* **PagHiper:** Choose this if you want a robust and automated solution that works with any Brazilian bank. PagHiper handles the charge generation and confirms the payment for you, automatically updating the order status in WooCommerce.
* **C6 Bank:** This is the perfect choice if you are a C6 Bank client. It offers a direct, secure, and automated integration with your bank account, also with automatic payment confirmation.

= Does the plugin automatically confirm payments and update the order status? =

* **Yes, for PagHiper and C6 Bank integrations.** These methods communicate directly with the payment provider's API to confirm payments in real-time and update the order status automatically (e.g., from "Pending payment" to "Processing").
* **No, for the Pix Key method.** This method only generates a QR Code for your key. You will need to manually verify that you have received the payment in your bank account and then update the order status yourself in WooCommerce.

= Is there any fee to use this plugin? =

* The plugin itself is free and open-source, released under the GPL license.
* However, the payment services you connect to may have their own transaction fees. C6 Bank and PagHiper have their own pricing plans for processing payments. The direct Pix Key method is usually subject to the fees of your own bank account for receiving payments. Please consult your payment provider for details.

= Do I need an SSL Certificate on my website? =

* **Yes, absolutely.** An SSL Certificate is essential for any e-commerce store. It encrypts the data between your customer's browser and your server, ensuring security and building trust. WooCommerce itself recommends (and often requires) an active SSL certificate to handle payments safely.

= Where do I find my API credentials for PagHiper or C6 Bank? =

* **For PagHiper:** You can find your `apiKey` and `token` by logging into your PagHiper account dashboard, usually in a section called "Credenciais" or "API".
* **For C6 Bank:** You will need to generate `Client ID` and `Client Secret` credentials for API access. This process is done within your C6 Bank business account portal. Please refer to C6 Bank's official documentation for API credentials.

== Support ==

If you need help or have questions, please post them in the [support forum](https://wordpress.org/support/plugin/payment-gateway-pix-for-woocommerce/) for the plugin on WordPress.org. We will be happy to assist you there.

== Changelog ==
# 1.5.0 - 2026/03/09
* New cron system for C6 PIX payment.

# 1.4.0 - 2025/12/24
* New Cielo Pix payment gateway.
* New Rede Pix payment gateway.

# 1.3.0 - 2025/11/17
* New PIX template for basic PIX.
* New PIX template for PagHiper.

# 1.2.2 - 2025/10/29
* Fix in basic PIX + PagHiper payment methods.

= 1.2.1 = *2025/09/25*
* Adjustments for the WordPress environment.
* Translation fixes.
* README.md update.

= 1.2.0 = *2025/09/22*
* New C6 PIX payment gateway.
* New C6 PIX template.

= 1.1.3 = *2025/04/23*
* Change in actions to enable automatic updates.

= 1.1.2 = *2025/02/25*
* Change in the default template of the PIX payment method.

= 1.1.1 = *2024/11/04*
* Added composer.json file in main.yml.
* Fixed script insertion.
* Updated PagHiper documentation.
* Corrected translation variable name.
* Added checks for $_POST and $_GET variables.
* Verified attribute and URL escaping in template content.

= 1.1.0 = *2024/09/04*
* Add modal to share Pix code;
* Add Pix QRCode in the administrator settings;
* Add setting to hide Pix code when the order is paid.

= 1.0.0 = *2024/08/19*
* Plugin launch.

== Upgrade Notice ==
= 1.2.2 =
* Fix payment pix.

= 1.2.1 =
* Adjustments for the WordPress environment.
* Translation fixes.
* README.md update.

= 1.2.0 =
* New C6 PIX payment gateway.
* New C6 PIX template.

= 1.1.0 =
* Add modal to share Pix code;
* Add Pix QRCode in the administrator settings;
* Add setting to hide Pix code when the order is paid.

= 1.0.0 =
* Plugin launch.
