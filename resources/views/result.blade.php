<!-- resources/views/result.blade.php -->

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color: #E5FFCC;">
    <div class="container mt-5">
        <div class="title" style="margin-bottom: 20px;">
            <h1 class="text-center">計算結果</h1>
        </div>
        <div class="form-container bg-white p-4 rounded shadow">

            <h2>基礎代謝(BMR)</h2>
            <p>生きていく上で最低限必要なカロリー消費</p>
            <div class="result">{{ $bmr }} kcal</div>

            <h2>活動代謝</h2>
            <p>運動や普段の生活で消費されたカロリー</p>
            <div class="result">{{ $activeMetabolism }} kcal</div>

            <h2>一日の消費カロリー</h2>
            <p>基礎代謝 + 活動代謝 = 一日の総カロリー消費</p>
            <div class="result">{{ $dailyMetabolism }} kcal</div>

            <h2>一日の減量値</h2>
            <p>一日の消費 - 一日の摂取 = 減量値</p>
            <div class="result" @if ($dailyCalorieDeficit < 0) style="color: #CC0000;" @endif>
                {{ $dailyCalorieDeficit }} kcal
            </div>

            <h2>期限経過時の体重</h2>
            <p>{{ $duration }}日経過した時点の予測される体重</p>
            <div class="result" @if ($weightAfterDuration > $goalWeight) style="color: #CC0000;" @endif>
                {{ $goalWeight }} > {{ $weightAfterDuration }} kg
            </div>

            <h2>目標達成の必要日数</h2>
            <p>目標に到達するまでにかかる日数。90日以上かかる場合は未達成となる。</p>
            <div class="result" @if ($daysNeeded < 0) style="color: #CC0000;" @endif>
                @if ($daysNeeded == -1)
                    未達成
                @else
                    {{ $daysNeeded }} 日
                @endif
            </div>

            <div class="linkbutton">
                <a href="{{ route('diet.back') }}">戻る</a>
            </div>
        </div>
    </div>
</body>
</html>
