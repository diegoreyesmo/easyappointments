# Plan: Asynchronous Email Notifications

## Objective
Eliminate the ~11-second blocking delay during appointment creation caused by synchronous email notifications. The goal is to process email sending in the background, returning a fast response to the user.

## Proposed Solution
Implement a **Database-Backed Email Queue with a CLI Worker**. 
This is the most robust and standard approach for CodeIgniter 3 applications. It avoids the unreliability of `exec`/`popen` background processes and does not require external dependencies like Redis or RabbitMQ, making it ideal for standard shared hosting or basic VPS environments.

## Implementation Steps

### 1. Database Schema Update
Create a new table `ea_email_queue` to store pending email jobs. This table uses a "reference-based" approach, storing the `appointment_id` and `recipient_email` rather than serializing complex email payloads. This keeps the queue lightweight and allows the worker to reuse existing email generation logic.

```sql
CREATE TABLE `ea_email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_type` varchar(50) NOT NULL, -- e.g., 'appointment_saved', 'appointment_deleted'
  `appointment_id` int(11) NOT NULL,
  `recipient_type` varchar(20) NOT NULL, -- 'customer', 'provider', 'admin', 'secretary'
  `recipient_email` varchar(255) NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `last_attempt` datetime DEFAULT NULL,
  `error_message` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `appointment_id` (`appointment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. Create Email Queue Model
Create `application/models/Email_queue_model.php` to handle queue operations:
- `add_to_queue($type, $appointment_id, $recipient_type, $recipient_email)`: Inserts a new job.
- `get_pending_jobs($limit = 50)`: Fetches pending jobs ordered by `created_at`.
- `mark_as_sent($id)`: Updates status to 'sent'.
- `mark_as_failed($id, $error)`: Updates status to 'failed', increments attempts, and logs the error.

### 3. Modify Notifications Library
Update `application/libraries/Notifications.php`:
- Intercept the calls to `$this->CI->email_messages->send_appointment_saved(...)`.
- Instead of executing them synchronously, call `$this->CI->email_queue_model->add_to_queue(...)` for each recipient (customer, provider, admins, secretaries).
- This reduces the notification step from ~11 seconds to a few milliseconds per recipient.

### 4. Create CLI Worker Controller
Create `application/controllers/Cli/Email_worker.php`:
- A CLI-only controller (protected by `is_cli()` check) with a `process` method.
- Fetches pending jobs from the queue (e.g., limit 50 per run to prevent timeouts).
- For each job, fetches the required data (`appointment`, `provider`, `service`, `customer`, `settings`) from the database.
- Calls the existing `Email_messages->send_appointment_saved()` or `send_appointment_deleted()` method.
- Updates the queue status to 'sent' or 'failed' based on the result.
- Includes a simple retry mechanism (e.g., max 3 attempts).

### 5. Setup Cron Job
Provide instructions to the user to add a cron job that runs the worker script every minute:
```bash
* * * * * /usr/bin/php /path/to/easyappointments/index.php cli email_worker process >/dev/null 2>&1
```
*(Note: The exact path to PHP and the application will need to be adjusted by the user based on their server environment).*

### 6. Backward Compatibility & Fallback
- Add a configuration setting (via database `ea_settings` or `application/config/config.php`): `email_queue_enabled` (default: `TRUE`).
- If disabled, the `Notifications` library falls back to the old synchronous behavior. This is useful for debugging or environments where cron jobs are absolutely impossible to configure.

## Trade-offs & Considerations
1. **Cron Job Requirement**: The user must have the ability to set up a cron job. 
2. **Delivery Delay**: The user will see the appointment saved immediately, but the email might take up to 1 minute to arrive. This is standard and expected for background email processing.
3. **Code Reuse**: By storing references (`appointment_id`) instead of serialized email content, we reuse the existing `Email_messages` library. This ensures no changes are needed for email formatting, ICS file attachments, or translation logic.

## Next Steps
1. Confirm if you have cron job access on your hosting environment.
2. Confirm if you agree with this database-backed queue approach.
3. Upon approval, I will implement the database migration, model, library updates, and CLI worker.
