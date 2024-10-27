<?php

namespace App\Http\Controllers;

use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;

class DietController extends Controller
{
    public function index()
    {   
        $age = "";
        $gender = "";
        $height = "";
        $nowWeight = "";
        $goalWeight = "";
        $duration = "";
        $dailyCalorieIntake = "";
        $activitiesData = "";
        
        return view('diet', compact(
            'age',
            'gender',
            'height',
            'nowWeight', 
            'goalWeight', 
            'duration',
            'dailyCalorieIntake'
         )); 
    }

    
    /**
     * resultページからトップページに戻る場合、ユーザー入力値を保持する。
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function back()
    {
        $age = session('age');
        $gender = session('gender');
        $height = session('height');
        $nowWeight = session('nowWeight');
        $goalWeight = session('goalWeight');
        $duration = session('duration');
        $dailyCalorieIntake = session('dailyCalorieIntake');
        $activitiesData = session('activities');
        
        return view('diet', compact(
            'age',
            'gender',
            'height',
            'nowWeight', 
            'goalWeight', 
            'duration',
            'dailyCalorieIntake'
         ));
    }


    /**
     * ユーザー入力値から各データを算出する。
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function calculate(Request $request)
    {  
        $validatedData = $request->validate([
            'age' => 'required|numeric|min:18|max:100',
            'gender' => 'required|in:male,female',
            'height' => 'required|numeric|regex:/^\d+(\.\d{1})?$/|between:120,250',
            'nowWeight' => 'required|numeric|regex:/^\d+(\.\d{1})?$/|between:30,300',
            'goalWeight' => 'required|numeric|regex:/^\d+(\.\d{1})?$/|between:30,300|lt:nowWeight',
            'duration' => 'required|numeric|min:7|max:90',
            'dailyCalorieIntake' => 'required|numeric|min:1000|max:4000'
        ], [
            'age' => '年齢: 半角数字18-100',
            'gender' => '性別指定',
            'height' => '身長: 半角数字50.0-250.0(cm)',
            'nowWeight' => '現在の体重: 半角数字30.0-300.0(kg)',
            'goalWeight' => '目標体重: 半角数字30.0-300.0(kg) 現在より減',
            'duration' => '期限: 半角英数7-90(日)',
            'dailyCalorieIntake' => '一日のカロリー摂取量: 1000-4000(kcal)'
        ]);

        $age = $request->input('age');
        $gender = $request->input('gender');
        $height = $request->input('height');
        $nowWeight = $request->input('nowWeight');
        $goalWeight = $request->input('goalWeight');
        $duration = $request->input('duration');
        $dailyCalorieIntake = $request->input('dailyCalorieIntake');
        $activitiesData = $request->input('activities');
        $activities = $this->parseActivities($activitiesData);

        //基礎代謝
        $bmr = $this->calculateBMR($age, $gender, $height, $nowWeight);
        //活動代謝
        $activeMetabolism = $this->calculateActiveMetabolism($activities, $nowWeight);
        //一日の消費カロリー
        $dailyMetabolism = $bmr + $activeMetabolism;
        //目標期限時の体重
        $weightAfterDuration = $this->calculateWeightAfterDuration($nowWeight, $duration, $dailyMetabolism, $dailyCalorieIntake);
        //一日に蓄積されるカロリー
        $dailyCalorieDeficit = $this->calculatedailyCalorieDeficit($dailyMetabolism, $dailyCalorieIntake);
        //目標達成までの日数
        $daysNeeded = $this->calculateDaysNeeded($nowWeight, $goalWeight, $dailyMetabolism, $dailyCalorieIntake);

        session([
            'age' => $age,
            'gender' => $gender,
            'height' => $height,
            'nowWeight' => $nowWeight,
            'goalWeight' => $goalWeight,
            'duration' => $duration,
            'dailyCalorieIntake' => $dailyCalorieIntake,
            'activities' => $activitiesData,
        ]);       

        return view('result', [
            'dailyCalorieIntake' => floor($dailyCalorieIntake),
            'nowWeight' => floor($nowWeight * 10) / 10,
            'goalWeight' => floor($goalWeight * 10) / 10,
            'duration' => floor($duration),
            'bmr' => floor($bmr),
            'activeMetabolism' => floor($activeMetabolism),
            'dailyMetabolism' => floor($dailyMetabolism),
            'dailyCalorieDeficit' => floor($dailyCalorieDeficit * 10) / 10, 
            'weightAfterDuration' => floor($weightAfterDuration * 10) / 10, 
            'daysNeeded' => floor($daysNeeded),
        ]);  
    }


    /**
     * 基礎代謝(BMR)を計算
     * ハリス・ベネディクト方程式使用
     * 男性：BMR=88.362+(13.397×体重(kg))+(4.799×身長(cm))−(5.677×年齢)
     * 女性：BMR=447.593+(9.247×体重(kg))+(3.098×身長(cm))−(4.330×年齢)
     * @param int $age 年齢
     * @param string $gender 性別 'male' or 'woman'
     * @param float $height 身長
     * @param float $weight 体重
     */
    public function calculateBMR($age, $gender, $height, $weight)
    {
        if ($gender === 'male') {
            $bmr = 88.362 + (13.397 * $weight) + (4.799 * $height) - (5.677 * $age);
        } else {
            $bmr = 447.593 + (9.247 * $weight) + (3.098 * $height) - (4.330 * $age);
        }
        return $bmr;
    }


    /**
    * 活動代謝を計算
    * METs × 体重(kg) × 時間(h)
    * durationは時間単位（例：0.33は20分）
    * @param array $activities 連想配列 [[type=>(種目),duration=>(分)], [type,duration], [...]] 空配列[]の場合は0が返る。
    * @param float $weight 体重 (kg)
    * @return float 活動代謝　アクティビティが不明の場合は-1.0が返る。
    */
    public function calculateActiveMetabolism($activities, $weight)
    {
        $totalCaloriesBurned = 0;

        foreach ($activities as $activity) {
            $activityType = $activity['type'];
            $duration = $activity['duration']; 
    
            switch ($activityType) {
                
                case 'デスクワーク':
                    $mets = 1.5;
                    break;
                case '軽作業':
                    $mets = 2.5;
                    break;
                case '肉体労働':
                    $mets = 7.0;
                    break;
                case '徒歩':
                    $mets = 3.3;
                    break;
                case '走る':
                    $mets = 9.8;
                    break;
                case '自転車':
                    $mets = 7.5;
                    break;
                case '軽い筋トレ':
                    $mets = 3.0;
                    break;
                case '筋トレ':
                    $mets = 6.0;
                    break;
                default:
                    return -1.0;
            }
            $caloriesBurned = $mets * $weight * ($duration / 60);// durationを時間単位に
            $totalCaloriesBurned += $caloriesBurned;
        }
        return $totalCaloriesBurned;
    }


    /**
     * フォームから受け取ったCSVを連想配列に変換
     * @param float $activitiesInput csv形式データ"waking,30;running,20" 入力が空の場合[]を返す。
     * @return array 連想配列 [[type=>(種目),duration=>(分)], [type,duration], [...]]
     */
    public function parseActivities($activitiesInput)
    {
        if (empty($activitiesInput)) {
            return [];
        }
        
        $activitiesData = explode(';', $activitiesInput);
        $activities = [];

        // $activityが空ならスキップ。
        // カンマ分割でtype, durationに分割できない場合[]を返す。
        // typeが文字列、duration が数値で無い場合は[]を返す。

        foreach ($activitiesData as $activity) {

            if (empty($activity)) {
                continue;
            }

            $parts = explode(',', $activity);//コンマ分割
            if (count($parts) !== 2) {
               return [];
            }

            list($type, $duration) = $parts;

            if (!is_string($type) || empty($type)) {
                return [];
            }

            if (!is_numeric($duration)) {
                return [];
            }

            $activities[] = [
                'type' => $type,
                'duration' => $duration
            ];
        }
        return $activities;
    }


    /**
     * 目標体重に到達するまでの日数を計算。
     * 1kg減に平均7700カロリーが必要と仮定する。
     * @param float $nowWeight 現在の体重
     * @param float $goalWeight 目標体重
     * @param float $dailyMetabolism 一日の活動消費
     * @param float $dailyCalorieIntake 一日のカロリー摂取量
     * @return float 日数　90日を超える場合、計算結果が0、または負数だった場合、達成不能として-1を返す。
     */
    public function calculateDaysNeeded($nowWeight, $goalWeight, $dailyMetabolism, $dailyCalorieIntake)
    {
        $weightLossNeeded = $nowWeight - $goalWeight;
        $caloriesToLose1Kg = 7700; 
        $dailyCalorieDeficit = $dailyMetabolism - $dailyCalorieIntake;

        $daysNeeded = ceil(($weightLossNeeded * $caloriesToLose1Kg) / $dailyCalorieDeficit);

        if ($daysNeeded <= 0 || $daysNeeded > 90) {
            return -1;
        }
    
        return $daysNeeded;
    }


    /**
     * 指定期限経過時の体重を計算する。
     * @param float $nowWeight 現在の体重
     * @param int $duration 実施期間（日数）
     * @param float $dailyMetabolism 一日の総カロリー消費量
     * @param float $dailyCalorieIntake 一日の摂取カロリー
     * @return float 指定期限経過時の体重
     */
    public function calculateWeightAfterDuration($nowWeight, $duration, $dailyMetabolism, $dailyCalorieIntake)
    {
        $dailyCalorieDeficit = $this->calculatedailyCalorieDeficit($dailyMetabolism, $dailyCalorieIntake);//一日のカロリー赤字
        $caloriesToLose1Kg = 7700;
        $weightLoss = ($dailyCalorieDeficit * $duration) / $caloriesToLose1Kg;
        $weightAfterDuration = $nowWeight - $weightLoss;

        return $weightAfterDuration;
    }


    /**
     * 一日で蓄積されるカロリー取得、プラス値、もしくはマイナス値
     * 
     * @param mixed $dailyMetabolism　一日で消費される総カロリー
     * @param mixed $dailyCalorieIntake　一日で摂取されるカロリー
     * @return float|int 一日で蓄積されたカロリー量
     */
    public function calculatedailyCalorieDeficit($dailyMetabolism, $dailyCalorieIntake)
    {
        return $dailyMetabolism - $dailyCalorieIntake;
    }

}
