<?php
namespace Tests\Feature;
namespace Tests\Unit;
use Tests\TestCase;
use App\Http\Controllers\DietController;
use Illuminate\Foundation\Testing\RefreshDatabase;


class DietControllerTest extends TestCase
{
    use RefreshDatabase;


    /**
     * 基礎代謝(BMR)を算出する。(男性)
     * @return void
     */
    public function test_calculateBMR_male()
    {
        $age = 25;
        $gender = 'male';
        $height = 180; // cm
        $weight = 75;  // kg
        $expectedBMR = 1815.032;

        $controller = new DietController();
        $calculatedBMR = $controller->calculateBMR($age, $gender, $height, $weight);

        $this->assertEquals($expectedBMR, $calculatedBMR, '');
    }


    /**
     * 基礎代謝(BMR)を算出する。(女性)
     * @return void
     */
    public function test_calculateBMR_female()
    {
        $age = 30;
        $gender = 'female';
        $height = 165; // cm
        $weight = 60;  // kg

        $expectedBMR = 1383.683;
        $controller = new DietController();
        $calculatedBMR = $controller->calculateBMR($age, $gender, $height, $weight);

        $this->assertEquals($expectedBMR, $calculatedBMR, '');
    }


    /**
     * 様々な活動による活動代謝の計算
     * @return void
     */
    public function test_calculateActiveMetabolism_with_various_activities()
    {
        $controller = new DietController();

        $activities = [
            ['type' => '徒歩', 'duration' => 60], // 1時間
            ['type' => '走る', 'duration' => 30],  // 30分
            ['type' => '自転車', 'duration' => 45],  // 45分
        ];
        $weight = 70; // 体重 (kg)

        // 期待値：期待される消費カロリーの計算
        $expectedCaloriesBurned = (3.3 * $weight * 1) + (9.8 * $weight * 0.5) + (7.5 * $weight * 0.75);

        $calculatedCalories = $controller->calculateActiveMetabolism($activities, $weight);

        $this->assertEquals($expectedCaloriesBurned, $calculatedCalories, '');
    }


    /**
     * 活動がない場合(基礎代謝のみで計算)
     * @return void
     */
    public function test_calculateActiveMetabolism_with_no_activities()
    {
        $controller = new DietController();
        $activities = [];
        $weight = 70; // 体重 (kg)

        // 期待値：期待される消費カロリーは0
        $expectedCaloriesBurned = 0;

        $calculatedCalories = $controller->calculateActiveMetabolism($activities, $weight);

        $this->assertEquals($expectedCaloriesBurned, $calculatedCalories);
    }


    /**
     * 不明な活動の処理
     * @return void
     */
    public function test_calculateActiveMetabolism_with_unknown_activity()
    {
        $controller = new DietController();
        $activities = [
            ['type' => 'ヨガ', 'duration' => 30],
        ];
        $weight = 70; // 体重 (kg)

        // 期待値：
        $expectedCaloriesBurned = -1.0;

        $calculatedCalories = $controller->calculateActiveMetabolism($activities, $weight);

        $this->assertEquals($expectedCaloriesBurned, $calculatedCalories);
    }


    /**
     * 正しいCSV形式のデータ
     * @return void
     */
    public function test_parseActivities()
    {
    $activitiesInput = "walking,30;running,20;cycling,15";
    
    $controller = new DietController();
    $expectedOutput = [
        ['type' => 'walking', 'duration' => 30],
        ['type' => 'running', 'duration' => 20],
        ['type' => 'cycling', 'duration' => 15],
    ];

    $result = $controller->parseActivities($activitiesInput);

    $this->assertEquals($expectedOutput, $result);
    }


    /**
     * 空の入力データ
     * @return void
     */
    public function test_parseActivities_with_empty_input()
    {
    $activitiesInput = "";
    
    $controller = new DietController();
    $expectedOutput = []; // 空の配列を期待

    $result = $controller->parseActivities($activitiesInput);

    $this->assertEquals($expectedOutput, $result);
    }


    /**
     * 無効なCSV形式のデータ
     * @return void
     */
    public function test_parseActivities_with_invalid_format()
    {
    $activitiesInput = "walking;running,20;cycling,15"; // フォーマットが不正
    
    $controller = new DietController();
    $expectedResult = []; // 空の配列を期待

    $result = $controller->parseActivities($activitiesInput);
    $this->assertEquals($expectedResult, $result);
    }


    /**
     * 目標達成予測日数の各テストケース
     * @return void
     */
    public function test_CalculateDaysNeeded()
    {
        $controller = new DietController();

        // テストケース 1: 目標体重に到達可能な場合
        $nowWeight = 80;          // 現在の体重
        $goalWeight = 70;         // 目標体重
        $dailyMetabolism = 2500;  // 一日の活動消費
        $dailyCalorieIntake = 2000; // 一日のカロリー摂取量

        // 7700カロリーで1kg減、10kg減のために77000カロリー必要
        // 1日のカロリー赤字は500カロリー (2500 - 2000)
        // 必要日数 = 77000 / 500 = 154日 → 計算結果は-1
        $expectedDays = -1;
        $calculatedDays = $controller->calculateDaysNeeded($nowWeight, $goalWeight, $dailyMetabolism, $dailyCalorieIntake);
        $this->assertEquals($expectedDays, $calculatedDays);

        // テストケース 2: 達成不能な場合
        $nowWeight = 75;          // 現在の体重
        $goalWeight = 80;         // 目標体重（増加）

        $expectedDays = -1; // 目標体重が現在の体重より多いので達成不能
        $calculatedDays = $controller->calculateDaysNeeded($nowWeight, $goalWeight, $dailyMetabolism, $dailyCalorieIntake);
        $this->assertEquals($expectedDays, $calculatedDays);

        // テストケース 3: 90日以内で達成できる場合
        $nowWeight = 80;          
        $goalWeight = 75;         
        $dailyMetabolism = 2500;  
        $dailyCalorieIntake = 1500; 

        // 7700カロリーで1kg減、5kg減のために38500カロリー必要
        // 1日のカロリー赤字は1000カロリー (2500 - 1500)
        // 必要日数 = 38500 / 1000 = 38.5 → 切り上げて39日
        $expectedDays = 39;
        $calculatedDays = $controller->calculateDaysNeeded($nowWeight, $goalWeight, $dailyMetabolism, $dailyCalorieIntake);
        $this->assertEquals($expectedDays, $calculatedDays);
    }


    /**
     * 目標期限到達時の体重計算
     * @return void
     */
    public function test_CalculateWeightAfterDuration()
    {
        $controller = new DietController();

        // テストケース 1: 目標体重に到達可能な場合
        $nowWeight = 80;            // 現在の体重
        $duration = 30;            // 実施期間（30日）
        $dailyMetabolism = 2500;   // 一日の総カロリー消費量
        $dailyCalorieIntake = 2000; // 一日の摂取カロリー

        // 一日のカロリー赤字は500カロリー (2500 - 2000)
        // 30日間で15000カロリーの赤字
        // 15000 / 7700 ≈ 1.948kgの減少
        // 80kg - 1.948kg ≈ 78.052kg
        $expectedWeightAfterDuration = round($nowWeight - (15000 / 7700), 3);
        $calculatedWeight = $controller->calculateWeightAfterDuration($nowWeight, $duration, $dailyMetabolism, $dailyCalorieIntake);
        $this->assertEqualsWithDelta($expectedWeightAfterDuration, $calculatedWeight, 0.001); // 許容誤差0.001

        // テストケース 2: 摂取カロリーが消費カロリーより多い場合
        $nowWeight = 80;            // 現在の体重
        $duration = 30;            // 実施期間（30日）
        $dailyMetabolism = 2500;   // 一日の総カロリー消費量
        $dailyCalorieIntake = 3000; // 一日の摂取カロリー

        // 一日のカロリー赤字は-500カロリー (2500 - 3000)
        // 30日間で-15000カロリー（体重増加）
        // 体重は増加するため、単純に現体重に加算
        $expectedWeightAfterDuration = $nowWeight + (15000 / 7700);
        $calculatedWeight = $controller->calculateWeightAfterDuration($nowWeight, $duration, $dailyMetabolism, $dailyCalorieIntake);
        $this->assertEquals(round($expectedWeightAfterDuration, 3), round($calculatedWeight, 3));

        // テストケース 3: カロリーが消費と等しい場合
        $nowWeight = 80;            // 現在の体重
        $duration = 30;            // 実施期間（30日）
        $dailyMetabolism = 2500;   // 一日の総カロリー消費量
        $dailyCalorieIntake = 2500; // 一日の摂取カロリー

        // 一日のカロリー赤字は0カロリー (2500 - 2500)
        // 体重は変化しない
        $expectedWeightAfterDuration = $nowWeight;
        $calculatedWeight = $controller->calculateWeightAfterDuration($nowWeight, $duration, $dailyMetabolism, $dailyCalorieIntake);
        $this->assertEquals($expectedWeightAfterDuration, $calculatedWeight);
    }


    /**
     * 有効なデータで全体のシミュレートテスト
     * @return void
     */
    public function test_calculate_with_valid_data()
    {
        $response = $this->post('/calculate', [
            'age' => 30,
            'gender' => 'male',
            'height' => 175.0,
            'nowWeight' => 80.0,
            'goalWeight' => 70.0,
            'duration' => 60,
            'dailyCalorieIntake' => 2500,
            'activities' => '走る,30;徒歩,60'
        ]);

        // ステータスコードが200であることを確認
        $response->assertStatus(200);

        // レスポンスビューに必要なデータが含まれていることを確認
        $response->assertViewHasAll([
            'bmr', 'activeMetabolism', 'dailyMetabolism', 'weightAfterDuration', 'daysNeeded'
        ]);
    }


    /**
     * 不正なデータによるバリデーションエラーテスト
     */
    public function test_calculate_with_invalid_data()
    {
        $response = $this->post('/calculate', [
            'age' => 15, // 年齢が18未満で無効
            'gender' => 'unknown', // 不正な性別
            'height' => 300.0, // 身長が範囲外
            'nowWeight' => 500.0, // 体重が範囲外
            'goalWeight' => 10.0, // 目標体重が範囲外
            'duration' => 200, // 期限が範囲外
            'dailyCalorieIntake' => 5000, // カロリー摂取量が範囲外
            'activities' => 'running,30'
        ]);

        // バリデーションエラーが発生してリダイレクトされることを確認
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'age', 'gender', 'height', 'nowWeight', 'goalWeight', 'duration', 'dailyCalorieIntake'
        ]);
    }



}