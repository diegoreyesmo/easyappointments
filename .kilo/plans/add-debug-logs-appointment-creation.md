# Plan: Add Debug Logs for Appointment Creation Performance

## Objective
Measure the execution time of each task during appointment creation to evaluate and optimize service performance.

## Scope
1. Update logging configuration to allow debug messages.
2. Add performance timing logs to the main appointment creation entry points:
   - `application/controllers/Calendar.php` (`save_appointment` method)
   - `application/controllers/Booking.php` (`register` method)
   - `application/controllers/api/v1/Appointments_api_v1.php` (`store` method)
3. Add timing inside `application/models/Appointments_model.php` (`save` method) to measure pure DB operation time.

## Implementation Steps

### 1. Configuration Change
- **File**: `application/config/config.php`
- **Action**: Change `$config['log_threshold']` from `1` to `4` (All Messages) or `2` (Debug Messages) to ensure `log_message('debug', ...)` outputs are written to `storage/logs/`.

### 2. Logging Pattern
Use `microtime(true)` to capture start and end times of specific blocks.
```php
$start_time = microtime(true);
// ... task execution ...
$duration = round((microtime(true) - $start_time) * 1000, 2);
log_message('debug', '[PERF] Appointment Creation - {task_name} took ' . $duration . 'ms');
```

### 3. Target Files & Tasks to Measure

#### A. `application/controllers/Calendar.php` (`save_appointment`)
Measure and log duration for:
- Total method execution.
- Customer data save/update.
- Provider conflict check.
- Appointment DB save (`$this->appointments_model->save()`).
- Synchronization (Google Calendar / CalDAV).
- Email notifications.
- Webhooks trigger.

#### B. `application/controllers/Booking.php` (`register`)
Measure and log duration for:
- Total method execution.
- CSRF & CAPTCHA/ALTCHA validation.
- Availability check (`check_datetime_availability`).
- Customer DB save.
- Appointment DB save (`$this->appointments_model->save()`).
- Synchronization (Google Calendar / CalDAV).
- Email notifications.
- Webhooks trigger.

#### C. `application/controllers/api/v1/Appointments_api_v1.php` (`store`)
Measure and log duration for:
- Total method execution.
- Appointment DB save.
- Notify and sync operations (`notify_and_sync_appointment`).

#### D. `application/models/Appointments_model.php` (`save`)
Measure and log duration for:
- Total `save` method execution.
- Validation (`$this->validate()`).
- Database insert or update query execution.

## Risks & Considerations
- **Disk I/O**: Writing debug logs on every appointment creation can impact disk I/O under high traffic. Monitor log file size and ensure log rotation is configured.
- **Security**: Ensure no sensitive data (PII, passwords, tokens) is logged. Only task names and execution durations will be recorded.
- **Environment**: This logging should ideally be enabled in staging or temporarily in production for measurement, then reverted to `$config['log_threshold'] = 1` if continuous high-volume logging is not desired.

## Next Steps
Once approved, I will implement these changes by adding the timing logic to the specified files.