# Table Booking REST API

REST API для бронирования столиков(PHP и MySQL)

## Что умеет

- показывает доступные столики
- создаёт, обновляет и отменяет бронирования
- возвращает JSON и HTTP-статусы
- использует PDO и подготовленные выражения

## Запуск через Docker

1. Скопируй пример окружения:

```bash
cp .env.example .env
```

2. Подними сервисы:

```bash
docker compose up --build
```

3. Открой:

```text
http://127.0.0.1:8000/
```

MySQL импортирует [schema.sql](schema.sql) при первом запуске.

## Эндпоинты

- `GET /api/tables/available?date=YYYY-MM-DD&start_time=HH:MM&end_time=HH:MM&guests_count=4`
- `POST /api/bookings`
- `GET /api/bookings?date=YYYY-MM-DD&status=confirmed&page=1&limit=10`
- `GET /api/bookings/{id}`
- `PUT /api/bookings/{id}`
- `DELETE /api/bookings/{id}`

## Пример создания

```json
{
  "table_id": 2,
  "guest_name": "Ivan Petrov",
  "guest_phone": "+79990000000",
  "booking_date": "2026-04-01",
  "start_time": "18:00",
  "end_time": "20:00",
  "guests_count": 4
}
```

## Ошибки

```json
{
  "error": "Invalid status value. Allowed values: confirmed, cancelled.",
  "details": {
    "status": "invalid_value"
  }
}
```

## Postman

Коллекция лежит в [postman/Table Booking API.postman_collection.json](postman/TableBookingAPI.postman_collection.json).
