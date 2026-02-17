<?php

namespace Antom\Core\Gateway;

class AntomConstants
{
    // ===== Generic wrappers =====
    public const BODY = 'body';
    public const HEADERS = 'headers';

    // ===== Headers fields =====
    public const CLIENT_ID = 'client-id';
    public const REQUEST_TIME = 'request-time';
    public const CONTENT_TYPE = 'Content-Type';
    public const SIGNATURE = 'signature';

    // ===== Top-level fields =====
    public const PRODUCT_CODE = 'productCode';
    public const PAYMENT_NOTIFY_URL = 'paymentNotifyUrl';
    public const PAYMENT_REDIRECT_URL = 'paymentRedirectUrl';
    public const CASHIER_PAYMENT = 'CASHIER_PAYMENT';
    public const MERCHANT_REGION = 'merchantRegion';
    public const MERCHANT_ACCOUNT_ID = 'merchantAccountId';
    public const USER_REGION = 'userRegion';
    public const ENV = 'env';
    public const ORDER = 'order';
    public const PAYMENT_REQUEST_ID = 'paymentRequestId';
    // db column
    public const ANTOM_PAYMENT_REQUEST_ID = 'antom_payment_request_id';
    public const PAYMENT_AMOUNT = 'paymentAmount';
    public const CAPTURE_AMOUNT = 'captureAmount';
    public const SETTLEMENT_STRATEGY = 'settlementStrategy';
    public const PAYMENT_METHOD = 'paymentMethod';
    public const PAYMENT_FACTOR = 'paymentFactor';
    public const CREDIT_PAY_PLAN = 'creditPayPlan';
    public const APP_ID = 'appId';
    public const PAYMENT_EXPIRY_TIME = 'paymentExpiryTime';

    // ===== env.* (environment / device context) =====
    public const TERMINAL_TYPE = 'terminalType';
    public const OS_TYPE = 'osType';
    public const ACCEPT_HEADER = 'acceptHeader';
    public const JAVA_ENABLED = 'javaEnabled';
    public const JAVA_SCRIPT_ENABLED = 'javaScriptEnabled';
    public const LANGUAGE = 'language';
    public const BROWSER_INFO = 'browserInfo';
    public const USER_AGENT = 'envUserAgent';
    public const COLOR_DEPTH = 'colorDepth';
    public const SCREEN_HEIGHT = 'screenHeight';
    public const SCREEN_WIDTH = 'screenWidth';
    public const TIME_ZONE_OFFSET = 'timeZoneOffset';
    public const DEVICE_BRAND = 'deviceBrand';
    public const DEVICE_MODEL = 'deviceModel';
    public const DEVICE_TOKEN_ID = 'deviceTokenId';
    public const CLIENT_IP = 'clientIp';
    public const DEVICE_LANGUAGE = 'deviceLanguage';
    public const DEVICE_ID = 'deviceId';
    public const EXTEND_INFO = 'ExtendInfo';

    // ===== order.* (order block) =====
    public const REFERENCE_ORDER_ID = 'referenceOrderId';
    public const ORDER_DESCRIPTION = 'orderDescription';
    public const ORDER_AMOUNT = 'orderAmount';

    // ===== order.goods[*].* =====
    public const GOODS = 'goods';
    public const REFERENCE_GOODS_ID = 'referenceGoodsId';
    public const GOODS_NAME = 'goodsName';
    public const GOODS_CATEGORY = 'goodsCategory';
    public const GOODS_QUANTITY = 'goodsQuantity';

    public const REFERENCE_BUYER_ID = 'referenceBuyerId';

    // payment additional_data
    public const CARD_TOKEN = 'card_token';

    // payment card payment
    public const IS_3DS = 'is3ds';


    // ===== paymentAmount.* =====
    public const CURRENCY = 'currency';
    public const VALUE = 'value';

    // ===== settlementStrategy.* =====
    public const SETTLEMENT_CURRENCY = 'settlementCurrency';

    // ===== paymentMethod.* =====
    public const PAYMENT_METHOD_TYPE = 'paymentMethodType';
    public const PAYMENT_METHOD_ID = 'paymentMethodId';
    public const PAYMENT_METHOD_META_DATA = 'paymentMethodMetaData';

    // ===== paymentFactor.* =====
    public const IS_AUTHORIZATION = 'isAuthorization';
    public const CAPTURE_MODE = 'captureMode';

    public const AMS_PAY_URI = '/ams/api/v1/payments/pay';
    public const UPDATE_SESSION_URI = '/ams/api/v1/payments/updatePaymentSession';
    public const MAGENTO_ALIPAY_CN = 'antom_alipay_cn';
    public const MAGENTO_ANTOM_CARD = 'antom_card';
    public const ALIPAY_CN = 'ALIPAY_CN';
    public const UNDERSCORE = '_';
    public const METHOD = 'method';
    public const URI = 'uri';
    public const CARD = 'CARD';
    public const POST = 'post';
    public const CLIENT_CONFIG = 'clientConfig';
    public const ANTOM_PUBLIC_KEY = 'antomPublicKey';
    public const MERCHANT_PRIVATE_KEY = 'merchanPrivateKey';
    public const GATEWAY_URL = 'gatewayUrl';
    public const RESULT = 'result';
    public const RESULT_STATUS = 'resultStatus';
    public const RESULT_CODE = 'resultCode';
    public const RESULT_MESSAGE = 'resultMessage';
    public const NORMAL_URL = 'normalUrl';
    public const S = 'S';
    public const F = 'F';
    public const U = 'U';
    public const PAYMENT_IN_PROCESS = 'PAYMENT_IN_PROCESS';
    public const PAYMENT_ID = 'paymentId';
    public const PAYMENT_STATUS = 'paymentStatus';
    public const FAIL = 'fail';
    public const INITIATED = 'initiated';
    public const REDIRECT = 'redirect';
    public const ACTION = 'action';
    public const PAYMENT_ACTION = 'paymentAction';
    public const SUCCESS = 'SUCCESS';
    public const UNKNOWN = 'UNKNOWN';
    public const LOWER_CASE_SUCCESS = 'success';
    public const NOTIFY_TYPE = 'notifyType';
    public const PAYMENT_RESULT = 'PAYMENT_RESULT';
    public const PAYMENT_PENDING = 'PAYMENT_PENDING';
    public const CAPTURE_RESULT = 'CAPTURE_RESULT';
    public const REFUND_RESULT = 'REFUND_RESULT';
    public const PAYMENT_CREATE_TIME = 'paymentCreateTime';
    public const AMOUNT = 'amount';
    public const CAPTURE_ID = 'captureId';
    public const STATUS = 'status';
    public const CAPTURE_REQUEST_ID = 'captureRequestId';

    public const ANTOM_ELEMENT_CARD_FAILED = 'antom_element_card_failed';
}
