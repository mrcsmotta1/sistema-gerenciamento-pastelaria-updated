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

use App\Http\Requests\ProductTypeApiRequest;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class ProductTypeRepository
 *
 * This Repository handles operations related to product type.
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
class ProductTypeRepository
{
    /**
     * Select All ProductType.
     *
     * @return ProductType The updated ProductType.
     */
    public function index(): Collection
    {
        return ProductType::query()->orderBy('name')->get();
    }

    /**
     * Add a new product type.
     *
     * @param ProductTypeApiRequest $request The HTTP ProductType data.
     *
     * @return ProductType The newly created ProductType.
     */
    public function add(ProductTypeApiRequest $request): ProductType
    {
        return DB::transaction(function () use ($request) {
            return ProductType::create($request->all());
        });
    }

    /**
     * Update an existing ProductType.
     *
     * @param ProductTypeApiRequest $request     The HTTP ProductType data.
     * @param string                $productTypeId productType ID
     *
     * @return ProductType The updated ProductType.
     */
    public function update(ProductTypeApiRequest $request, $productTypeId): ProductType
    {
        try {
            DB::beginTransaction();
            $productType = $this->find($productTypeId);
            $productType = $productType->fill($request->all());
            $productType->save();
            DB::commit();

            return $productType;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Delete a ProductType by their ID.
     *
     * @param string $productType The ID of the ProductType to delete.
     *
     * @return void
     */
    public function destroy(string $productType): void
    {
        try {
            DB::beginTransaction();
            ProductType::destroy($productType);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Restore a soft-deleted Product Type.
     *
     * @param string $productType The ID of the productType to restore.
     *
     * @return void
     */
    public function restore(string $productType): void
    {
        $restore = ProductType::withTrashed()->where(['id' => $productType]);
        $restore->restore();
    }

    /**
     * Display the specified product Type.
     *
     * @param string $productType The unique identifier of the product Type to restore.
     *
     * @return \Illuminate\Http\Response
     */
    public function find(string $productType)
    {
        return ProductType::find($productType);
    }

     /**
     * Display the specified customer onlyTrashed.
     *
     * @param string $customer The unique identifier of the customer to restore.
     *
     * @return \Illuminate\Http\Response
     */
    public function findOnlyTrashed(string $productType)
    {
        return ProductType::onlyTrashed()->find($productType);
    }
}
