# Line Payment

Very easy and light package for Line Payment.

## Installation
### Composer
Add Laravel Localization to your composer.json file

    "rohit/line-pay": "^1.0"
Run `composer install` to get the latest version of package

Or you can directly run the `composer require` command

    composer require rohit/line-pay

### Configuration
After the package install is completed you need to configure `config/app.php` and add `Providers` and `Aliases`

```php
    'providers` => [
        .......
        .......
        Rohit\LinePay\LinePayServiceProvider::class
    ]
```
```php
    'aliases' => [
        ......
        ......
        'LinePay' => Rohit\LinePay\Facades\LinePay::class
    ]
```

### Vendor Publish
After the above steps, you need to publish vendor for this packge. It will create `line-pay.php` file under `config` folder. This folder contains the configuration for your locales.

    php artisan vendor:publish --provider="Rohit\LinePay\LinePayServiceProvider"

The file `line-pay.php` will contain the following structure
```php
    return [
        'channel-id' => 'Line Pay Channel Id',
        'channel-secret' => 'Line Pay Channel Secret',
        'reservation-url' => 'https://sandbox-api-pay.line.me/v1/payments/request',
        'detail-url' => 'https://sandbox-api-pay.line.me/v1/payments',
    ];
```
### Functions
* #### Process Payment
    You can process your payment with the function process Payment which accepts array as an arguments with following
    ```php
    LinePay::processPayment([
        'productName' => 'Name of Product you want user to pay for',
        'amount' => 'Amount of the product',
        'currency' => 'Currency Code eg: USD',
        'orderId' => 'Unique Order Id',
        'confirmUrl' => 'Url to which Line will redirect after successful of payment',
        'cancelUrl' => 'Url to which Line will redirect when user cancel the payment',
    ])
    ```
    The response from the process payment will be as below
    ```php
    [
        'status' => 'success' or 'failed',
        'data' => [
            'request-payload' => 'Json string of request payload (You may need to save it to log for future)',
            'response-payload' => 'Json string of response payload (You may need to save it to log for future)' or 'null if failed',
            'transaction-id' => 'Transaction Id if success' or 'null if failed',
            'url' => 'Line Url for payment if success' or 'null if failed',
        ],
    ]
    ```
* #### Process Payment
    After Successful payment from line, it redirects to the `confirmUrl`. Now we need to verify the payment from line before updating our order. Its a 3 way handshake for security.
    This function takes 2 arguments. TransactionId and the array of parameters.
    ```php
    LinePay::verifyPayment($transactionId, [
        'amount' => 'Amount of the Order',
        'currency' => 'Currency Code eg: USD',
    ]);
    ```
    The response from the verify payment will be as below
    ```php
    [
        'status' => 'success' or 'failed',
        'data' => [
            'request-payload' => 'Json string of request payload (You may need to save it to log for future)',
            'response-payload' => 'Json string of response payload (You may need to save it to log for future)' or 'null if failed',
            'transaction-id' => 'Transaction Id',
        ],
    ]
    ```
