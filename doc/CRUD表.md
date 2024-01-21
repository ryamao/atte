# CRUD表

## ワークセット×エンティティ

| ワークセット | users | shift_begins | shift_timings | break_begins | break_timings |
| --- | --- | --- | --- | --- | --- |
| 登録バリデーション | R |   |   |   |   |
| 会員登録処理 | C |   |   |   |   |
| ログインバリデーション |   |   |   |   |   |
| ログイン処理 | R |   |   |   |   |
| 打刻ページ初期化処理 |   | RD | C | R |   |
| 勤務開始処理 |   | CR |   |   |   |
| 勤務終了処理 |   | RD | C | R |   |
| 休憩開始処理 |   | R |   | CR |   |
| 休憩終了処理 |   |   |   | RD | C |
| 当日の日付別勤怠情報取得 | R |   | R |   | R |
| 前日の日付別勤怠情報取得 | R |   | R |   | R |
| 翌日の日付別勤怠情報取得 | R |   | R |   | R |
| 前ページの日付別勤怠情報取得 | R |   | R |   | R |
| 次ページの日付別勤怠情報取得 | R |   | R |   | R |

## ルート×エンティティ

| ルート | users | shift_begins | shift_timings | break_begins | break_timings |
| --- | --- | --- | --- | --- | --- |
| GET /register |   |   |   |   |   |
| POST /register | CR |   |   |   |   |
| GET /login |   |   |   |   |   |
| POST /login | R |   |   |   |   |
| POST /logout |   |   |   |   |   |
| GET / |   | RD | C | R |   |
| POST /shift/begin |   | CR |   |   |   |
| POST /shift/end |   | RD | C | R |   |
| POST /break/begin |   | R |   | CR |   |
| POST /break/end |   |   |   | RD | C |
| GET /attendance | R |   | R |   | R |
