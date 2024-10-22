<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diet Simulator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body style="background-color: #E5FFCC;">
    <div class="container mt-5">
        <div class="title">
            <h1 class="text-center">Diet Simulator</h1>
            <h3 class="text-center">beta</h3>
        </div>
    <div class="description mt-4 mb-4"> 
        <p>
            ダイエットシミュレーターは、目標体重を達成するための期間と一日に必要な活動量の目安を計算します。<br>
            <br>
            各データを入力し「計算する」をクリック。             
        </p>
    </div>
    
    @if ($errors->any())
        <div class='validate'>
            <div class="alert alert-danger">
                <label class="form-label">入力に誤りがあります。</label>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>  
    @endif
  
        <div class="form-container bg-white p-4 rounded shadow">
            
            <form action="{{ route('diet.calculate') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="age" class="form-label">年齢 (歳):</label>
                    <input type="number" name="age" id="age" class="form-control"  value="{{ old('age', $age) }}" required>
                </div>

                <div class="mb-4">
                    <label for="gender" class="form-label">性別:</label>
                    <select name="gender" id="gender" class="form-select" required>
                        <option value="male" {{ old('gender', $gender) == 'male' ? 'selected' : '' }}>男性</option>
                        <option value="female" {{ old('gender', $gender) == 'female' ? 'selected' : '' }}>女性</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="height" class="form-label">身長 (cm):</label>
                    <input type="number" name="height" id="height" class="form-control" step="0.1" value="{{ old('height', $height) }}" required>
                </div>

                <div class="mb-4">
                    <label for="nowWeight" class="form-label">現在の体重 (kg):</label>
                    <input type="number" name="nowWeight" id="nowWeight" class="form-control" step="0.1" value="{{ old('nowWeight', $nowWeight) }}" required>
                </div>

                <div class="mb-4">
                    <label for="goalWeight" class="form-label">目標体重 (kg):</label>
                    <input type="number" name="goalWeight" id="goalWeight" class="form-control" step="0.1" value="{{ old('goalWeight', $goalWeight) }}" required>
                </div>

                <div class="mb-4">
                    <label for="duration" class="form-label">実施期間 (日):</label>
                    <p>
                        目標を達成するまでの予定期間(チェックポイント)<br><br>
                        一週間～３ヶ月までを指定します。結果がそれ以上かかる場合は未達成となります。<br><br>
                    </p>
                    <input type="number" name="duration" id="duration" class="form-control" value="{{ old('duration', $duration) }}" required>
                </div>
                <div class="mb-4">
                    <label for="dailyCalorieIntake" class="form-label">一日の摂取カロリー (kcal):</label>
                    <p>表は日本人の一日の平均的摂取カロリーです。<br><br></p>
                    
                    <div class="mb-4">
                        <table>
                            <thead>
                                <tr>
                                    <th>年代</th>
                                    <th>男性</th>
                                    <th>女性</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>19-30</td>
                                    <td>2300～2650</td>
                                    <td>1750～2050</td>
                                </tr>
                                <tr>
                                    <td>31-50</td>
                                    <td>2300～2650</td>
                                    <td>1700～2050</td>
                                </tr>
                                <tr>
                                    <td>51-70</td>
                                    <td>2100～2500</td>
                                    <td>1650～2000</td>
                                </tr>
                                <tr>
                                    <td>71-</td>
                                    <td>2000～2300</td>
                                    <td>1600～1850</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <input type="number" name="dailyCalorieIntake" id="dailyCalorieIntake" class="form-control" value="{{ old('dailyCalorieIntake', $dailyCalorieIntake) }}" required>             
                </div>

                <div class="activity">
                    <div class="mb-4">
                        <label for="activitySelect" class="form-label">運動種目:</label>
                        <p>
                            一日の活動量の目安。<br><br>
                            通勤で徒歩往復20分なら「徒歩」「20」と入力し、追加ボタンをクリック。最小10分。<br><br>
                            全く活動が無い場合は未入力(基礎代謝のみで計算)
                            複数の種目を設定できます。<br><br>
                        </p>
                        <select id="activitySelect" name="activity" class="form-select">
                            <option value="walking" {{ old('activity') == 'walking' ? 'selected' : '' }}>徒歩</option>
                            <option value="running" {{ old('activity') == 'running' ? 'selected' : '' }}>走る</option>
                            <option value="cycling" {{ old('activity') == 'cycling' ? 'selected' : '' }}>自転車</option>
                            <option value="training-light" {{ old('activity') == 'training-light' ? 'selected' : '' }}>軽い筋トレ</option>
                            <option value="training" {{ old('activity') == 'training' ? 'selected' : '' }}>中程度の筋トレ</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="activityDuration" class="form-label">時間 (分):</label>
                        <input type="number" name="activityDuration" id="activityDuration" class="form-control" min="10" max="720" value="{{ old('activityDuration') }}">
                    </div>
                    <div class="mb-4 text-end">
                        <button type="button" class="btn btn-custom rounded" onclick="removeActivity()">クリア</button>
                        <button type="button" class="btn btn-custom rounded" onclick="addActivity()">追加</button>
                    </div>
                    <label class="form-label">一日の活動:</label>
                    <ul id="activityList" class="list-group mb-3"></ul>
                    <input type="hidden" name="activities" id="activities">
                </div>
                
                <div class="mb-4">
                    <button type="submit" class="btn btn-custom rounded w-100">計算する</button>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let activities = [];

        function addActivity() {
            const activitySelect = document.getElementById('activitySelect');
            const activityDuration = document.getElementById('activityDuration');

            let activityType = activitySelect.value;
            let duration = activityDuration.value;

            if (duration < 10) {
                duration = 10;
            } else if (duration > 720) {
                duration = 720;
            }

            if (activityType && duration) {
                activities.push(`${activityType},${duration}`);
                document.getElementById('activityList').innerHTML += `<li class="list-group-item">${activityType}: ${duration} 分</li>`;
                document.getElementById('activities').value = activities.join(';');
            }
        }

        function removeActivity() {
            activities = [];
            document.getElementById('activityList').innerHTML = '';
            document.getElementById('activities').value = '';
        }
    </script>
</body>
</html>
