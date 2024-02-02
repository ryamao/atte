# CRUD表

## ルート×エンティティ

| ルート | users | shift_begins | shift_timings | break_begins | break_timings |
| --- | --- | --- | --- | --- | --- |
| GET /register |   |   |   |   |   |
| POST /register | CR |   |   |   |   |
| GET /login |   |   |   |   |   |
| POST /login | R |   |   |   |   |
| POST /logout |   |   |   |   |   |
| GET / |   | RD | C | R |   |
| POST /shift/begin |   | CR | RD |   |   |
| POST /shift/end |   | RD | C | R |   |
| POST /break/begin |   | R |   | CR |   |
| POST /break/end |   |   |   | RD | C |
| GET /attendance | R | R | R | R | R |

## ワークセット×エンティティ

| ワークセット | users | shift_begins | shift_timings | break_begins | break_timings |
| --- | --- | --- | --- | --- | --- |
| [会員登録する](ワークセット.md#会員登録する) | CR |   |   |   |   |
| [ログイン認証する](ワークセット.md#ログイン認証する) | R |   |   |   |   |
| [日付を跨いだ勤務を終了する](ワークセット.md#日付を跨いだ勤務を終了する) |   | RD | C |   |   |
| [日付を跨いだ休憩を終了する](ワークセット.md#日付を跨いだ休憩を終了する) |   |    |   | RD | C |
| [勤務状況を取得する](ワークセット.md#勤務状況を取得する) |   | R |   | R |   |
| [勤務開始日時を保存する](ワークセット.md#勤務開始日時を保存する) |   | C | RD |   |   |
| [勤務履歴を保存する](ワークセット.md#勤務履歴を保存する) |   | D | C |   |   |
| [休憩開始日時を保存する](ワークセット.md#休憩開始日時を保存する) |   |   |   | C |   |
| [休憩履歴を保存する](ワークセット.md#休憩履歴を保存する) |   |   |   | D | C |
| [全会員の勤務情報を日付指定で取得する](ワークセット.md#全会員の勤務情報を日付指定で取得する) | R | R | R |   |   |
| [全会員の休憩情報を日付指定で取得する](ワークセット.md#全会員の休憩情報を日付指定で取得する) | R |   |   | R | R |
