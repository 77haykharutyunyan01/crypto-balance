# crypto-balance

Тестовое задание: модуль зачисления и списания крипто-баланса пользователя с учётом рисков (PHP, Laravel).

---

## Запуск

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Сидер создаёт двух пользователей (`test@example.com`, `test2@example.com`) — их `id` подставляй в URL при запросах к API (например `/api/users/1/deposit`).

---

## API

| Метод | URL | Body (JSON) |
|-------|-----|-------------|
| POST | `/api/users/{id}/deposit` | `{"currency": "BTC", "amount": 0.5, "risk_level": "normal"}` или `"high"` |
| POST | `/api/users/{id}/withdraw` | `{"currency": "BTC", "amount": 0.1}` |
| POST | `/api/transactions/{id}/confirm` | `{}` |
| POST | `/api/transactions/{id}/fail` | `{}` |

В ответе приходят `transaction` и текущий `account` (available/locked).

---

## Тесты

```bash
php artisan test
```

В `tests/Feature/CryptoBalanceTest.php` проверяются: обычный депозит, high-risk депозит и confirm, списание при нехватке баланса, high-risk вывод и fail (возврат средств).

---

## Структура

- **Таблицы:** `crypto_accounts` (баланс по user + валюта: available + locked), `crypto_transactions` (история операций, статус, risk_level).
- **Сервис:** `App\Services\CryptoBalanceService` — deposit, withdraw, confirmTransaction, failTransaction. Всё в транзакциях БД с блокировкой счёта (`lockForUpdate`).
- **Риски:** при `risk_level: high` сумма идёт в locked и ждёт confirm/fail; при confirm — перевод в available (депозит) или списание из locked (вывод); при fail — отмена и при выводе возврат в available.

---

## Что сделано и зачем

1. **Два баланса (available / locked)** — чтобы рискованные операции не сразу меняли доступные средства; после подтверждения в блокчейне вызывается confirm, при ошибке — fail.
2. **Транзакции БД + lockForUpdate** — чтобы при одновременных запросах не было гонок и двойного списания.
3. **Проверка баланса при списании** — при нехватке available выбрасывается исключение, списание не выполняется.
4. **Запись каждой операции в `crypto_transactions`** — аудит, тип (deposit/withdrawal), направление (credit/debit), статус (pending/completed/failed).
5. **Тесты** — сценарии из задани
