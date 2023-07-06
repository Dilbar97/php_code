<?php


use common\models\Coins;
use Google\Protobuf\Timestamp;
use Grpc\ChannelCredentials;

class GrpcClient
{
    public GrpcClient $client;

    public $response;

    public function __construct()
    {
        $this->client = new GrpcClient(getenv("GRPC_SERVICE_HOST"), [
            'credentials' => ChannelCredentials::createInsecure(),
        ]);
    }

    public function profits($coinID): self
    {
        $coinData = Coins::findOne(['id' => $coinID]);

        $allProfitsRequest = new AllProfitsRequest();

        $allProfitsRequest->setCoin($coinData->code);

        list($this->response, $status) = $this->client->GetAllProfits($allProfitsRequest)->wait();

        if(!$this->response) {
            \Yii::error($status, 'status');
        }

        return $this;
    }

    public function profit($coinID, $date): self
    {
        $coinData = Coins::findOne(['id' => $coinID]);

        $profitRequest = new ProfitByDateRequest();

        $profitRequest->setCoin($coinData->code);
        $profitRequest->setDate((new Timestamp())->setSeconds(strtotime($date)));

        list($this->response, $status) = $this->client->GetProfitByDate($profitRequest)->wait();

        if(!$this->response) {
            \Yii::error($status, 'status');
        }

        return $this;
    }

    public function avgProfitByDateRange($coinID, $dateFrom, $dateTo): float
    {
        $coinData = Coins::findOne(['id' => $coinID]);

        $getAvgProfitRequest = new AvgProfitByDateRangeRequest();

        $getAvgProfitRequest->setCoin($coinData->code);
        $getAvgProfitRequest->setFrom((new Timestamp())->setSeconds(strtotime($dateFrom)));
        $getAvgProfitRequest->setTo((new Timestamp())->setSeconds(strtotime($dateTo)));

        list($response, $status) = $this->client->GetAvgProfitByDateRange($getAvgProfitRequest)->wait();

        if(!$response) {
            \Yii::error($status, 'status');
        }

        if (empty($response)) {
            return 0;
        }

        return $response->getAvgRate();
    }

    public function all()
    {
        return !empty($this->response) ? $this->response->getProfits() : [];
    }

    public function one()
    {
        return !empty($this->response) ? $this->response->getProfits()[0] : null;
    }

    public function get()
    {
        return !empty($this->response) ? $this->response->getProfit() : null;
    }

    public function asArray(): array
    {
        $res = [];

        foreach ($this->all() as $key =>  $profit) {
            $res[$key]['date'] = date('Y-m-d', $profit->getDate()->getSeconds());
            $res[$key]['rate'] = $profit->getRate();
        }

        return $res;
    }

    public function getStatus(): int
    {
        if (!empty($this->response) && empty($this->response->all())) {
            return 0; // awaiting
        }

        return 1; //completed
    }
}
