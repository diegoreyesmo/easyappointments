<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * AgendaRRF - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.5.0
 * ---------------------------------------------------------------------------- */

/**
 * Payments model.
 *
 * Handles all the database operations of the payment_transactions resource.
 *
 * @package Models
 */
class Payments_model extends EA_Model
{
    protected array $casts = [
        'id' => 'integer',
        'id_appointments' => 'integer',
        'id_users_customer' => 'integer',
        'amount' => 'float',
    ];

    protected array $allowed_transaction_fields = [
        'id',
        'id_appointments',
        'id_users_customer',
        'payment_method',
        'preference_id',
        'payment_id',
        'status',
        'amount',
        'currency',
        'mp_response',
    ];

    /**
     * Save (insert or update) a payment transaction.
     *
     * @param array $transaction Associative array with the transaction data.
     * @return int Returns the transaction ID.
     * @throws InvalidArgumentException
     */
    public function save(array $transaction): int
    {
        $this->validate($transaction);

        if (empty($transaction['id'])) {
            return $this->insert($transaction);
        } else {
            return $this->update($transaction);
        }
    }

    /**
     * Validate the transaction data.
     *
     * @param array $transaction Associative array with the transaction data.
     * @throws InvalidArgumentException
     */
    public function validate(array $transaction): void
    {
        if (!empty($transaction['id'])) {
            $count = $this->db->get_where('payment_transactions', ['id' => $transaction['id']])->num_rows();
            if (!$count) {
                throw new InvalidArgumentException(
                    'The provided transaction ID does not exist in the database: ' . $transaction['id'],
                );
            }
        }

        if (empty($transaction['id_appointments']) || empty($transaction['payment_method'])) {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($transaction, true));
        }
    }

    /**
     * Insert a new transaction into the database.
     *
     * @param array $transaction Associative array with the transaction data.
     * @return int Returns the transaction ID.
     * @throws RuntimeException
     */
    protected function insert(array $transaction): int
    {
        $transaction['create_datetime'] = date('Y-m-d H:i:s');
        $transaction['update_datetime'] = date('Y-m-d H:i:s');

        if (!$this->db->insert('payment_transactions', $transaction)) {
            throw new RuntimeException('Could not insert payment transaction.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing transaction.
     *
     * @param array $transaction Associative array with the transaction data.
     * @return int Returns the transaction ID.
     * @throws RuntimeException
     */
    protected function update(array $transaction): int
    {
        $transaction['update_datetime'] = date('Y-m-d H:i:s');

        if (!$this->db->update('payment_transactions', $transaction, ['id' => $transaction['id']])) {
            throw new RuntimeException('Could not update payment transaction record.');
        }

        return $transaction['id'];
    }

    /**
     * Find a transaction by appointment ID.
     *
     * @param int $appointment_id Appointment ID
     * @return array|null Returns transaction data or null if not found
     */
    public function find_by_appointment_id(int $appointment_id): ?array
    {
        $transaction = $this->db
            ->where('id_appointments', $appointment_id)
            ->order_by('id', 'DESC')
            ->get('payment_transactions')
            ->row_array();

        if ($transaction) {
            $this->cast($transaction);
        }

        return $transaction ?: null;
    }

    /**
     * Find a transaction by MercadoPago payment ID.
     *
     * @param string $payment_id MercadoPago payment ID
     * @return array|null Returns transaction data or null if not found
     */
    public function find_by_payment_id(string $payment_id): ?array
    {
        $transaction = $this->db
            ->where('payment_id', $payment_id)
            ->get('payment_transactions')
            ->row_array();

        if ($transaction) {
            $this->cast($transaction);
        }

        return $transaction ?: null;
    }

    /**
     * Find a transaction by external reference (appointment hash).
     *
     * @param string $external_reference External reference
     * @return array|null Returns transaction data or null if not found
     */
    public function find_by_external_reference(string $external_reference): ?array
    {
        $this->load->model('appointments_model');

        $appointment = $this->appointments_model->get(['hash' => $external_reference]);

        if (empty($appointment)) {
            return null;
        }

        return $this->find_by_appointment_id((int) $appointment[0]['id']);
    }

    /**
     * Get all transactions for a customer.
     *
     * @param int $customer_id Customer user ID
     * @return array Returns array of transactions
     */
    public function get_transactions_by_customer(int $customer_id): array
    {
        $transactions = $this->db
            ->where('id_users_customer', $customer_id)
            ->order_by('create_datetime', 'DESC')
            ->get('payment_transactions')
            ->result_array();

        foreach ($transactions as &$transaction) {
            $this->cast($transaction);
        }

        return $transactions;
    }

    /**
     * Get a specific transaction.
     *
     * @param int $transaction_id Transaction ID
     * @return array Returns transaction data
     * @throws InvalidArgumentException
     */
    public function find(int $transaction_id): array
    {
        $transaction = $this->db->get_where('payment_transactions', ['id' => $transaction_id])->row_array();

        if (!$transaction) {
            throw new InvalidArgumentException(
                'The provided transaction ID was not found in the database: ' . $transaction_id,
            );
        }

        $this->cast($transaction);

        return $transaction;
    }

    /**
     * Update the status of a transaction.
     *
     * @param int $transaction_id Transaction ID
     * @param string $status New status
     * @return bool
     */
    public function update_status(int $transaction_id, string $status): bool
    {
        return $this->db->update(
            'payment_transactions',
            ['status' => $status, 'update_datetime' => date('Y-m-d H:i:s')],
            ['id' => $transaction_id],
        );
    }

    /**
     * Filter transaction data to only allowed fields.
     *
     * @param array &$transaction Transaction data reference
     */
    public function only(array &$transaction): void
    {
        $transaction = array_intersect_key($transaction, array_flip($this->allowed_transaction_fields));
    }
}
