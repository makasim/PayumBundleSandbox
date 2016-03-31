<?php

namespace Acme\PaymentBundle\Payum\Paypal\ExpressCheckout\Nvp;

use PayPal\Auth\Oauth\AuthSignature;
use Payum\Core\Exception\Http\HttpException;
use Payum\Paypal\ExpressCheckout\Nvp;
use Payum\Paypal\ExpressCheckout\Nvp\Api;
use GuzzleHttp\Psr7\Request;


/**
 * Created by Dmitry Prokopenko <hellsigner@gmail.com>
 * Date: 30.03.16
 * Time: 16:58
 */
class ApiViaToken extends Api
{

    protected $options = array(
        'username' => null,
        'password' => null,
        'signature' => null,
        'return_url' => null,
        'cancel_url' => null,
        'sandbox' => null,
        'useraction' => null,
        'token' => null,
        'tokenSecret' => null,
        'cmd' => Api::CMD_EXPRESS_CHECKOUT,
    );

    /**
     * @param array $fields
     *
     * @throws HttpException
     *
     * @return array
     */
    protected function doRequest(array $fields)
    {
        $headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        );

        $method = 'POST';
        $this->addAuthorizeHeader($headers, $method);

        $request = new Request($method, $this->getApiEndpoint(), $headers, http_build_query($fields));

        $response = $this->client->send($request);

        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        $result = array();
        parse_str($response->getBody()->getContents(), $result);
        foreach ($result as &$value) {
            $value = urldecode($value);
        }

        return $result;
    }

    /**
     * @param array $fields
     */
    protected function addAuthorizeFields(array &$fields)
    {
//        parent::addAuthorizeFields($fields);
        $fields['PWD'] = $this->options['password'];
        $fields['USER'] = $this->options['username'];
        $fields['SIGNATURE'] = $this->options['signature'];

        $fields['SUBJECT'] = $this->options['third_party_subject'];
    }


    protected function addAuthorizeHeader(array &$headers, $method = 'POST')
    {
        $authSignature = AuthSignature::generateFullAuthString(
            $this->options['username'],
            $this->options['password'],
            $this->options['token'],
            $this->options['tokenSecret'],
            $method,
            $this->getApiEndpoint()
        );

        $headers['X-PAYPAL-AUTHORIZATION'] = $authSignature;
    }

}