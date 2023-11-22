<?php

/**
 * MyClass File Doc Comment
 * php version 8.1
 *
 * @category Repository
 * @package  App\Repositories
 * @author   Marcos Motta <mrcsmotta1@gmail.com>
 * @license  MIT License
 * @link     https://github.com/mrcsmotta1/sistema-gerenciamento-pastelaria
 */

namespace App\Repositories;

use App\Http\Requests\CustomerApiRequest;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

/**
 * Class CustomerRepository
 *
 * This Repository handles operations related to customers.
 *
 * @phpcs
 * php version 8.1
 *
 * @category Repository
 * @package  App\Repositories
 * @author   Marcos Motta <mrcsmotta1@gmail.com>
 * @license  MIT License
 * @link     https://github.com/mrcsmotta1/sistema-gerenciamento-pastelaria
 */
class CustomerRepository
{
    /**
     * Select All customer.
     *
     * @return Customer The updated customer.
     */
    public function index()
    {
        return DB::transaction(function () {
            return Customer::query()->orderBy('name')->get();
        });
    }

    /**
     * Add a new customer.
     *
     * @param CustomerApiRequest $request The HTTP request containing customer data.
     *
     * @return Customer The newly created customer.
     */
    public function add(CustomerApiRequest $request): Customer
    {
        return DB::transaction(function () use ($request) {
            return Customer::create($request->all());
        });
    }

    /**
     * Update an existing customer.
     *
     * @param Customer           $customer The customer to update.
     * @param CustomerApiRequest $request  The HTTP request with customer data.
     *
     * @return Customer The updated customer.
     *
     *
     */
    public function update(CustomerApiRequest $request): Customer
    {
        try {
            DB::beginTransaction();
            $customer = $this->find($request->id);
            $customer = $customer->fill($request->all());
            $customer->save();
            DB::commit();

            return $customer;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Delete a customer by their ID.
     *
     * @param string $customer The ID of the customer to delete.
     *
     * @return void
     */
    public function destroy(string $customer): void
    {

        try {
            DB::beginTransaction();
            $customerData = json_decode($customer, true);

            Customer::destroy($customerData['id']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Restores a customer by their unique identifier.
     *
     * @param int $customer The unique identifier of the customer to restore.
     *
     * @return \Illuminate\Http\Response
     */
    public function restore(string $customer)
    {
        try {
            DB::beginTransaction();
            $restoreExist = $this->findOnlyTrashed($customer);

            $restoreExist->restore();
            DB::commit();

            return response()->json(['message' => 'Customer restored successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Display the specified customer.
     *
     * @param string $customer The unique identifier of the customer to restore.
     *
     * @return \Illuminate\Http\Response
     */
    public function find(string $customer)
    {
        return Customer::find($customer);
    }

    /**
     * Display the specified customer onlyTrashed.
     *
     * @param string $customer The unique identifier of the customer to restore.
     *
     * @return \Illuminate\Http\Response
     */
    public function findOnlyTrashed(string $customer)
    {
        return Customer::onlyTrashed()->find($customer);
    }
}
