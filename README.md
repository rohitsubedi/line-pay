# Line Payment

Very easy and light package for Line Payment.

## Installation
### Composer
Add Line Pay to your composer.json file

    "rohit/line-pay": "^3.0"
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
    LinePay::processPayment($params);
    ```
    The array params for processPayment is described below:

    | Items  | Datatype  | Required | Description  | Remarks  |
    |---|---|:---:|---|---|
    | productName  |  string (4000byte)  | Y | Name of Product you want user to pay for  |
    | amount  | numeric  | Y | Amount of the product  |
    | currency  | string (3byte)  | Y  | Payment Currency  | supported Currencies are `(USD, JPY, TWD, THB)` |
    | orderId | string (100byte) | Y | Order Number corresponding to payment request | Must be unique for each order |
    | confirmUrl | string (500byte) | Y | Url to which Line will redirect after successful of payment |
    | cancelUrl | string (500byte) | N | Url to which Line will redirect when user cancel the payment | Default redirect to request URL. No additional parameters is sent by LINE
    | productImageUrl | string (500byte) | N | Url to Product Image |
    | mid | string (50byte) | N | Line member ID |
    | oneTimeKey | string (12byte) | N | Scanning and reading QR/Bar code information given by LINE pay app is used as LINE pay users\'s mid. Valid time is 5 minutes and it will be deleted with reserve at the same time |
    | confirmUrlType | string | N | Type of URL that the buyer is redirected to after selecting payment method and entering the payment password in LINE pay | `CLIENT`: A user based URL `(DEFAULT)`.<br /> `SERVER`: A server based URL |
    | checkConfirmUrlBrowser | Boolean | N | When moved to confirmUrl. check a browser | `true`: When a browser calling a payment and browser directing to confirmUrl are different LINE pay provides a guide page directing to a previous browser. <br />`false`: Redirecting to confirmUrl without checking a browser `(DEFAULT)` |
    | packageName | string (4000byte) | N | Information to avoid phishing during transition between apps in Android |
    | deliveryPlacePhone | string (100byte) | N | Receipent Contact Number |
    | payType | string (12byte) | N | Payment Types | `NORMAL`: Single Payment `(DEFAULT)`<br />`PREAPPROVED`: Preapproved Payment |
    | langCd | string | N | Language code on the payment waiting screen | Supported Languages are:<br /> - `ja` (Japanese)<br /> - `ko` (Korean)<br /> - `en` (English) `(DEFAULT)`<br /> - `zh-Hans` (Chinese Simplified)<br /> - `zh-Hant` (Chinese Traditional)<br /> - `th` (Thai) |
    | capture | Boolean | N | Whether to capture or not | - `true`: Payment authorization and capture are handled at once when the confirm payment API is called `(DEFAULT)`<br /> - `false`: A payment is compleed only after it is authorized and then separately captured by calling Capture API when confirm payment API is called |

    The response from the process payment will be as below

    ```php
    [
        'status' => 'success' or 'failed',
        'data' => [
            'request' => 'Params you send while calling the function (You may need to save it to log for future)',
            'response' => 'Array response (You may need to save it to log for future)',
        ],
    ]

    The response will be empty [] or as follows:
    [
        'returnCode' => '0000' (if success) or other code,
        'returnMessage' -> 'OK',
        'info' => [
            'transactionId' => 'Transaction Id eg: 12345678',
            'paymentUrl' => [
                'web' => 'Payment url for web.' (Need to redirect to this for payment),
                'app' => 'Payment url for app',
            ],
            'paymentAccessToken' => 'Access Token for Payment'
        ],
    ]
    ```
* #### Verify Payment
    After Successful payment from line, it redirects to the `confirmUrl`. Now we need to verify the payment from line before updating our order. Its a 3 way handshake for security.
    This function takes 2 arguments. TransactionId and the array of parameters.

    ```php
    LinePay::verifyPayment($transactionId, $params);
    ```

    The array param for verifyPayment is described below:

    | Items  | Datatype  | Required | Description  | Remarks  |
    |---|---|:---:|---|---|
    | amount | numeric | Y | Payment Amount | Should match with the amount on Process Payment for the given transaction Id. |
    | currency| string (3byte) | Y | Payment Currency | Should match with Currency on Process Payment for the given transaction Id.<br /> Supported Languages are:<br /> - `ja` (Japanese)<br /> - `ko` (Korean)<br /> - `en` (English) `(DEFAULT)`<br /> - `zh-Hans` (Chinese Simplified)<br /> - `zh-Hant` (Chinese Traditional)<br /> - `th` (Thai)<br />

    The response from the verify payment will be as below

    ```php
    [
        'status' => 'success' or 'failed',
        'data' => [
            'request' => 'Params you send while calling the function (You may need to save it to log for future)',
            'response' => 'Array response (You may need to save it to log for future)',
        ],
    ]
    ```
    The response will be empty [] or as follows:
    ```php
    If payment Type is NORMAL the response will be as follow:
    [
        'returnCode' => '0000' (if success) or other code,
        'returnMessage' -> 'OK',
        'info' => [
            'orderId' => 'OrderID of the payment',
            'transactionId' => 'Transaction Id',
            'payInfo' => [
                [
                    'method' => 'BALANCE',
                    'amount' => '10',
                ],
                [
                    'method' => 'DISCOUNT',
                    'amount' => '10',
                ],
            ],
        ],
    ]
    ```
    ```php
    If payment Type is PREAPPROVED the response will be as follow:
    [
        'returnCode' => '0000' (if success) or other code,
        'returnMessage' -> 'OK',
        'info' => [
            'orderId' => 'OrderID of the payment',
            'transactionId' => 'Transaction Id',
            'payInfo' => [
                [
                    'method' => 'CREDIT_CARD',
                    'amount' => '10',
                    'creditCardNickName' => 'test',
                    'creditCardBrand' => 'VISA',
                ],
                'regKey' => 'Random reg Key',
            ],
        ],
    ]
    ```
* #### Capture Payment
    If `capture` is false while processing payment by `processPayment` function, then the payment is completed only after the Catpute API is called. This function completes the payment that was only authorized by `processPayment`.
    This function takes 2 arguments. TransactionId and the array of parameters.

    ```php
    LinePay::verifyPayment($transactionId, $params);
    ```

    The array param for verifyPayment is described below:

    | Items  | Datatype  | Required | Description  | Remarks  |
    |---|---|:---:|---|---|
    | amount | numeric | Y | Payment Amount | Should match with the amount on Process Payment for the given transaction Id. |
    | currency| string (3byte) | Y | Payment Currency | Should match with Currency on Process Payment for the given transaction Id.<br /> Supported Languages are:<br /> - `ja` (Japanese)<br /> - `ko` (Korean)<br /> - `en` (English) `(DEFAULT)`<br /> - `zh-Hans` (Chinese Simplified)<br /> - `zh-Hant` (Chinese Traditional)<br /> - `th` (Thai)<br />

    The response from the verify payment will be as below

    ```php
    [
        'status' => 'success' or 'failed',
        'data' => [
            'request' => 'Params you send while calling the function (You may need to save it to log for future)',
            'response' => 'Array response (You may need to save it to log for future)',
        ],
    ]

    The response will be empty [] or as follows:
    [
        'returnCode' => '0000' (if success) or other code,
        'returnMessage' -> 'OK',
        'info' => [
            'orderId' => 'The order id',
            'transactionId' => 'Transaction Id eg: 12345678',
            'info' => [
                [
                    'method' => 'BALANCE',
                    'amount' => 10,
                ],
                [
                    'method' => 'DISCOUNT',
                    'amount' => 10,
                ],
            ],
        ],
    ]
    ```
* #### Void Payment
    Voids a previously authorized payment. A payment that has been already captured can be refunded by this API

    ```php
    LinePay::voidPayment($transactionId);
    ```
    The response from the void payment will be as below

    ```php
    [
        'status' => 'success' or 'failed',
        'data' => [
            'request' => [],
            'response' => 'Array response (You may need to save it to log for future)',
        ],
    ]

    The response will be empty [] or as follows:
    [
        'statusCode' => '0000' (if success) or other code,
        'returnMessage' => 'OK',
    ]
