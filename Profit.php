<?php

class Profit
{
    public $profits;

    /** Получаем все исторические данные по профиту */
    public function getAllProfits($coinCode)
    {
        if (!$coin = Coins::findOne(['code'=> $coinCode])){
            \Yii::error("Не найден код монеты {$coinCode}", 'profit');
            return [];
        }

        $profits = (new GrpcClient())->profits($coin->id)->asArray();

        if(!$profits) {
            \Yii::error("Ошибка получения исторических данных по профиту {$coinCode}", 'profit');
        } else {
            $tmpArr = ArrayHelper::map($profits, 'date', 'rate');
            $tmpArr = array_reverse($tmpArr);
        }

        $this->profits = $tmpArr ?? [];
    }

    /**
     * Получаем среднее значение профита за диапазон дат.
     * По сути этот метод был сделан, чтобы повысить производительность и не обращаться в другой сервис за данными
     * @param $dateFrom string дата начала (включительно) YYYY-MM-DD
     * @param $dateTo string дата окончания (включительно) YYYY-MM-DD
     * @return float|null
     */
    public function getAvarageProfit($dateFrom = null, $dateTo = null)
    {
        /** Если нет массива профитов - пытаемся получить */
        if(empty($this->profits)) {
            $this->getAllProfits($this->coin);
        }

        if(empty($this->profits)) return 0;

        $start_date  = $dateFrom ?? array_key_first($this->profits);
        $stop_date   = $dateTo ?? array_key_last($this->profits);

        if(!key_exists($start_date, $this->profits)) {
            $start_date =  array_key_first($this->profits);
        }

        if(!key_exists($stop_date, $this->profits)) {
            $start_date =  array_key_last($this->profits);
        }

        $a_keys = array_keys($this->profits);
        $start_index = array_search($start_date, $a_keys);
        $stop_index = array_search($stop_date, $a_keys);

        $needle_array = array_slice($this->profits, $start_index, $stop_index-$start_index+1);

        if(count($needle_array) > 0) {
            return array_sum($needle_array)/count($needle_array);
        }

        return 0;
    }

    /**
     * Получаем значение профита за дату
     * @param $date string дата YYYY-MM-DD
     * @return float|null
     */
    public function getProfitByDate($date)
    {
        /** Если нет массива профитов - пытаемся получить */
        if(empty($this->profits)) {
            $this->getAllProfits($this->coin);
        }

        return $this->profits[$date] ?? 0;
    }
}
