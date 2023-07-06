<?php

use common\models\Coins;
use yii\base\Component;
use yii\httpclient\Client;

class Ebc extends Component
{
    public $client;

    /** @var $base_url string */
    public string $base_url = '';

    /** @var $access_key string */
    public string $access_key = '';

    public function init()
    {
        parent::init();

        $this->client = new Client([
            'baseUrl' => $this->base_url,
            'transport' => 'yii\httpclient\CurlTransport'
        ]);
    }

    public function getBalances()
    {
        $balances = [];
        $coins = Coins::find()->all();
        foreach ($coins as $key => $coin) {
            try {
                $response = $this->client->createRequest()
                    ->setUrl('balance/' . $coin->code)
                    ->setMethod("GET")
                    ->addHeaders(['x-access-token' => $this->access_key])
                    ->send();

                $balances[$key]['coinCode'] = $coin->code;
                $balances[$key]['balance'] = $response->data['balance'];
            } catch (\Exception $e) {
                \Yii::error($e->getMessage(), 'balances');
                continue;
            }
        }

        return $balances;
    }

    public function getPairsDirections()
    {
        try {
            $response = $this->client->createRequest()
                ->setUrl('pairs-directions')
                ->setMethod("GET")
                ->addHeaders(['x-access-token' => $this->access_key])
                ->send();

            return $response->data['data'];
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'balances');
            return [];
        }
    }

    public function updatePairDirection($pair, $direction)
    {
        try {
            $data = [
                'pair' => $pair,
                'direction' => $direction
            ];

            $response = $this->client->createRequest()
                ->setUrl('pair-direction')
                ->setMethod("POST")
                ->addHeaders(['x-access-token' => $this->access_key])
                ->setFormat(Client::FORMAT_JSON)
                ->setData($data)
                ->send();

            return $response->statusCode;
        } catch (\Exception $e) {
            //var_dump($e->getMessage());exit;
            \Yii::error($e->getMessage(), 'balances');
            return false;
        }
    }

    public function getPairsStatuses()
    {
        try {
            $response = $this->client->createRequest()
                ->setUrl('pair-statuses')
                ->setMethod("GET")
                ->addHeaders(['x-access-token' => $this->access_key])
                ->send();
            return $response->data['states'];
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'statuses');
            return [];
        }
    }

    public function updatePairsStatuses($data)
    {
        try {
            if(empty($data) || !is_array($data)) return false;

            $pairs = [];
            foreach($data as $pair => $status) {
                $pairs['pairs'][$pair] = $status;
            }

            $response = $this->client->createRequest()
                ->setUrl('pair-statuses')
                ->setMethod("PUT")
                ->addHeaders(['x-access-token' => $this->access_key])
                ->setFormat(Client::FORMAT_JSON)
                ->setData($pairs)
                ->send();

            return $response->statusCode;

        } catch (\Exception $e) {
            //var_dump($e->getMessage());exit;
            \Yii::error($e->getMessage(), 'statuses');
            return false;
        }
    }


    public function getStatuses() {
        return [
          'both_sell' => \Yii::t('base', 'Withdrawal is available for both services (Russian and foreign cards)'),
          'wb_sell' => \Yii::t('base', 'Withdrawal is available only via Whitebird (foreign cards)'),
          'mp_sell' => \Yii::t('base', 'Withdrawal is available only via mpsPay/Garantex (Russian cards)'),
          'block' => \Yii::t('base', ' Withdrawal is not available for both services (Russian and foreign cards)'),
        ];
    }
}
